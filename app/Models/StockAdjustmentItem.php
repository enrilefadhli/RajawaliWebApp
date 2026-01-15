<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\StockMovement;

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

                $stockBefore = (int) $batch->quantity;
                $newQty = $stockBefore + (int) $item->qty_change;
                if ($newQty < 0) {
                    throw ValidationException::withMessages([
                        'qty_change' => 'Resulting quantity would be negative for this batch.',
                    ]);
                }

                $item->product_id = $batch->product_id;

                $batch->update(['quantity' => $newQty]);

                if ((int) $item->qty_change !== 0) {
                    $movementType = self::resolveMovementType($item->stock_adjustment_id);
                    $direction = ((int) $item->qty_change) >= 0 ? 'IN' : 'OUT';

                    StockMovement::create([
                        'product_id' => $batch->product_id,
                        'batch_of_stock_id' => $batch->id,
                        'movement_type' => $movementType,
                        'direction' => $direction,
                        'quantity' => abs((int) $item->qty_change),
                        'stock_before' => $stockBefore,
                        'stock_after' => $newQty,
                        'source_type' => 'stock_adjustment',
                        'source_id' => $item->stock_adjustment_id,
                        'created_by' => self::resolveCreatorId($item->stock_adjustment_id),
                        'notes' => $item->notes,
                    ]);
                }
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
                $stockBefore = (int) $batch->quantity;
                $newQty = $stockBefore + $delta;

                if ($newQty < 0) {
                    throw ValidationException::withMessages([
                        'qty_change' => 'Resulting quantity would be negative for this batch.',
                    ]);
                }

                $item->product_id = $batch->product_id;

                $batch->update(['quantity' => $newQty]);

                if ($delta !== 0) {
                    $movementType = self::resolveMovementType($item->stock_adjustment_id);
                    $direction = $delta >= 0 ? 'IN' : 'OUT';

                    StockMovement::create([
                        'product_id' => $batch->product_id,
                        'batch_of_stock_id' => $batch->id,
                        'movement_type' => $movementType,
                        'direction' => $direction,
                        'quantity' => abs($delta),
                        'stock_before' => $stockBefore,
                        'stock_after' => $newQty,
                        'source_type' => 'stock_adjustment',
                        'source_id' => $item->stock_adjustment_id,
                        'created_by' => self::resolveCreatorId($item->stock_adjustment_id),
                        'notes' => $item->notes,
                    ]);
                }
            });
        });

        static::deleting(function (StockAdjustmentItem $item) {
            DB::transaction(function () use ($item) {
                $batch = BatchOfStock::lockForUpdate()->findOrFail($item->batch_of_stock_id);
                $stockBefore = (int) $batch->quantity;
                $delta = 0 - (int) $item->qty_change;
                $newQty = $stockBefore + $delta;

                if ($newQty < 0) {
                    throw ValidationException::withMessages([
                        'qty_change' => 'Resulting quantity would be negative for this batch.',
                    ]);
                }

                $batch->update(['quantity' => $newQty]);

                if ($delta !== 0) {
                    $movementType = self::resolveMovementType($item->stock_adjustment_id);
                    $direction = $delta >= 0 ? 'IN' : 'OUT';

                    StockMovement::create([
                        'product_id' => $batch->product_id,
                        'batch_of_stock_id' => $batch->id,
                        'movement_type' => $movementType,
                        'direction' => $direction,
                        'quantity' => abs($delta),
                        'stock_before' => $stockBefore,
                        'stock_after' => $newQty,
                        'source_type' => 'stock_adjustment',
                        'source_id' => $item->stock_adjustment_id,
                        'created_by' => self::resolveCreatorId($item->stock_adjustment_id),
                        'notes' => $item->notes,
                    ]);
                }
            });
        });
    }

    private static function resolveMovementType(?int $stockAdjustmentId): string
    {
        if (! $stockAdjustmentId) {
            return 'ADJUSTMENT';
        }

        $reason = StockAdjustment::whereKey($stockAdjustmentId)->value('reason');

        return $reason === 'OPNAME' ? 'OPNAME' : 'ADJUSTMENT';
    }

    private static function resolveCreatorId(?int $stockAdjustmentId): ?int
    {
        if (! $stockAdjustmentId) {
            return null;
        }

        return StockAdjustment::whereKey($stockAdjustmentId)->value('created_by');
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
