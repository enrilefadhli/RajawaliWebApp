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
                    $batch->batch_no = self::generateUniqueBatchCode($batch->expiry_date);
                });
            }
        });
    }

    protected static function generateUniqueBatchCode($expiryDate): string
    {
        $today = now()->format('Ymd');
        $exp = $expiryDate ? date('Ymd', strtotime((string) $expiryDate)) : '00000000';
        $pattern = "B-{$today}-{$exp}-%";

        $latest = self::lockForUpdate()
            ->where('batch_no', 'like', $pattern)
            ->orderByDesc('batch_no')
            ->value('batch_no');

        $nextSeq = 1;
        if ($latest && preg_match("/^B-{$today}-{$exp}-(\\d+)$/", $latest, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        $seq = str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);

        return Str::upper("B-{$today}-{$exp}-{$seq}");
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
