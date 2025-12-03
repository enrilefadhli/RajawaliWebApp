<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'batch_of_stock_id',
        'product_id',
        'qty_change',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockAdjustmentItem $item) {
            DB::transaction(function () use ($item) {
                $batch = BatchOfStock::lockForUpdate()->findOrFail($item->batch_of_stock_id);

                $newQty = $batch->quantity + $item->qty_change;
                if ($newQty < 0) {
                    throw ValidationException::withMessages([
                        'qty_change' => 'Resulting quantity would be negative for this batch.',
                    ]);
                }

                $item->product_id = $batch->product_id;

                $batch->update(['quantity' => $newQty]);
            });
        });

        static::updating(function (StockAdjustmentItem $item) {
            DB::transaction(function () use ($item) {
                if ($item->isDirty('batch_of_stock_id')) {
                    throw ValidationException::withMessages([
                        'batch_of_stock_id' => 'Changing the batch on an existing adjustment item is not allowed.',
                    ]);
                }

                $batch = BatchOfStock::lockForUpdate()->findOrFail($item->batch_of_stock_id);
                $originalChange = (int) $item->getOriginal('qty_change');
                $delta = (int) $item->qty_change - $originalChange;
                $newQty = $batch->quantity + $delta;

                if ($newQty < 0) {
                    throw ValidationException::withMessages([
                        'qty_change' => 'Resulting quantity would be negative for this batch.',
                    ]);
                }

                $item->product_id = $batch->product_id;

                $batch->update(['quantity' => $newQty]);
            });
        });

        static::deleting(function (StockAdjustmentItem $item) {
            DB::transaction(function () use ($item) {
                $batch = BatchOfStock::lockForUpdate()->findOrFail($item->batch_of_stock_id);
                $newQty = $batch->quantity - $item->qty_change;

                if ($newQty < 0) {
                    throw ValidationException::withMessages([
                        'qty_change' => 'Resulting quantity would be negative for this batch.',
                    ]);
                }

                $batch->update(['quantity' => $newQty]);
            });
        });
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchOfStock::class, 'batch_of_stock_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
