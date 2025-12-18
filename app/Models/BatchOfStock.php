<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Notifications\LowStockNotification;
use App\Models\User;
use App\Models\Product;
use App\Models\SystemSetting;

class BatchOfStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'batch_no',
        'expiry_date',
        'quantity',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (BatchOfStock $batch) {
            if (! $batch->expiry_date) {
                throw new \InvalidArgumentException('Expiry date is required for FEFO tracking.');
            }

            if (empty($batch->batch_no)) {
                DB::transaction(function () use ($batch) {
                    $productCode = $batch->product?->product_code ?? \App\Models\Product::find($batch->product_id)?->product_code ?? 'NOPROD';
                    $batch->batch_no = self::generateUniqueBatchCode($productCode);
                });
            }
        });

        static::saved(function (BatchOfStock $batch) {
            self::notifyIfLow($batch->product_id);
        });

        static::deleted(function (BatchOfStock $batch) {
            self::notifyIfLow($batch->product_id);
        });
    }

    protected static function generateUniqueBatchCode(?string $productCode): string
    {
        $productCode = strtoupper(trim((string) $productCode)) ?: 'NOPROD';
        $productCode = preg_replace('/[^A-Z0-9]/', '', $productCode);

        $today = now()->format('Ymd');
        $base = "B{$productCode}{$today}";
        $pattern = "{$base}%";

        $latest = self::lockForUpdate()
            ->where('batch_no', 'like', $pattern)
            ->orderByDesc('batch_no')
            ->value('batch_no');

        $nextSeq = 1;
        $regex = '/^' . preg_quote($base, '/') . '(\\d+)$/';
        if ($latest && preg_match($regex, $latest, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        $seq = str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);

        return Str::upper("{$base}{$seq}");
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockOpnameItems(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class, 'batch_of_stock_id');
    }

    public function stockAdjustmentItems(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'batch_of_stock_id');
    }

    /**
     * Deduct stock using FEFO (first expiring, first out) across batches.
     */
    public static function deductFefo(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($productId, $quantity) {
            $remaining = $quantity;

            $batches = self::where('product_id', $productId)
                ->where('quantity', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
                })
                ->orderByRaw('expiry_date IS NULL')
                ->orderBy('expiry_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($batch->quantity, $remaining);
                $batch->decrement('quantity', $deduct);
                $remaining -= $deduct;
            }

            if ($remaining > 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient unexpired stock to fulfill the request.',
                ]);
            }

            self::notifyIfLow($productId);
        });
    }

    protected static function notifyIfLow(int $productId): void
    {
        $settings = SystemSetting::first();

        $alertsEnabled = $settings?->low_stock_alerts_enabled ?? config('inventory.low_stock_alerts_enabled');
        $emailEnabled = $settings?->low_stock_email_enabled ?? config('inventory.low_stock_email_enabled');
        $telegramEnabled = $settings?->low_stock_telegram_enabled ?? config('inventory.low_stock_telegram_enabled');
        $thresholdPercent = (int) ($settings?->low_stock_threshold_percent ?? config('inventory.low_stock_threshold_percent'));
        $thresholdEnabled = (bool) ($settings?->low_stock_threshold_enabled ?? config('inventory.low_stock_threshold_enabled'));

        if (! $alertsEnabled) {
            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $available = $product->batchOfStocks()
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
            })
            ->sum('quantity');

        $minimumStock = (int) ($product->minimum_stock ?? 0);
        if ($minimumStock <= 0) {
            return;
        }

        $thresholdPercent = max(1, min(500, $thresholdPercent));
        $thresholdQty = (int) floor($minimumStock * ($thresholdPercent / 100));

        $isLow = false;
        if ($product->status === 'ACTIVE') {
            $isLow = $available < $minimumStock;

            if (! $isLow && $thresholdEnabled) {
                $isLow = $available <= $thresholdQty;
            }
        }
        if (! $isLow) {
            return;
        }

        $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'ADMIN'))->get();
        if ($admins->isEmpty()) {
            return;
        }

        $notification = new LowStockNotification($product, $available, $settings);

        if ($telegramEnabled) {
            $notification->sendTelegramAlert();
        }

        if ($emailEnabled) {
            // Apply SMTP overrides if provided in settings (fall back to env otherwise).
            if ($settings?->mail_username) {
                config(['mail.mailers.smtp.username' => $settings->mail_username]);
            }
            if ($settings?->mail_password) {
                config(['mail.mailers.smtp.password' => $settings->mail_password]);
            }

            Notification::send($admins, $notification);
        }
    }
}
