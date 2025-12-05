<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

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
            if (empty($batch->batch_no)) {
                DB::transaction(function () use ($batch) {
                    $productCode = $batch->product?->product_code ?? \App\Models\Product::find($batch->product_id)?->product_code ?? 'NOPROD';
                    $expiryDate = $batch->expiry_date;
                    $batch->batch_no = self::generateUniqueBatchCode($productCode, $expiryDate);
                });
            }
        });
    }

    protected static function generateUniqueBatchCode(?string $productCode, $expiryDate): string
    {
        $productCode = strtoupper(trim((string) $productCode)) ?: 'NOPROD';
        $productCode = preg_replace('/[^A-Z0-9]/', '', $productCode);

        $expiry = $expiryDate ? date('Ymd', strtotime((string) $expiryDate)) : null;
        if (! $expiry) {
            throw new \InvalidArgumentException('Expiry date is required for batch code generation.');
        }

        $base = "B{$productCode}{$expiry}";
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
        });
    }
}
