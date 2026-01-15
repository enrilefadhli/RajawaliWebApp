<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\BatchOfStock;
use App\Models\StockMovement;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'supplier_id',
        'purchase_order_id',
        'total_amount',
        'status',
        'notes',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'voided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Purchase $purchase): void {
            if (empty($purchase->code)) {
                $prefix = $purchase->purchase_order_id ? 'AP-' : 'DP-';
                $purchase->code = app(\App\Services\CodeGeneratorService::class)->generate(
                    prefix: $prefix,
                    table: $purchase->getTable(),
                    column: 'code',
                    date: $purchase->created_at ?? now()
                );
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function voidPurchase(?int $userId, ?string $reason = null): void
    {
        if ($this->status === 'VOIDED') {
            throw ValidationException::withMessages([
                'status' => 'Purchase is already voided.',
            ]);
        }

        DB::transaction(function () use ($userId, $reason) {
            $detailIds = $this->details()->pluck('id');

            $movements = StockMovement::query()
                ->where('movement_type', 'PURCHASE')
                ->where('direction', 'IN')
                ->where('source_type', 'purchase_detail')
                ->whereIn('source_id', $detailIds)
                ->lockForUpdate()
                ->get();

            foreach ($movements as $movement) {
                if (! $movement->batch_of_stock_id) {
                    throw ValidationException::withMessages([
                        'stock' => 'Cannot void purchase: missing batch reference.',
                    ]);
                }

                $batch = BatchOfStock::lockForUpdate()->find($movement->batch_of_stock_id);
                if (! $batch) {
                    throw ValidationException::withMessages([
                        'stock' => 'Cannot void purchase: batch not found.',
                    ]);
                }

                if ((int) $batch->quantity < (int) $movement->quantity) {
                    throw ValidationException::withMessages([
                        'stock' => "Cannot void purchase: batch {$batch->batch_no} has insufficient quantity.",
                    ]);
                }

                $stockBefore = (int) $batch->quantity;
                $stockAfter = $stockBefore - (int) $movement->quantity;
                $batch->update(['quantity' => $stockAfter]);

                StockMovement::create([
                    'product_id' => $batch->product_id,
                    'batch_of_stock_id' => $batch->id,
                    'movement_type' => 'PURCHASE',
                    'direction' => 'OUT',
                    'quantity' => (int) $movement->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'source_type' => 'purchase_void',
                    'source_id' => $this->id,
                    'created_by' => $userId,
                    'notes' => $reason,
                ]);
            }

            $this->forceFill([
                'status' => 'VOIDED',
                'voided_at' => now(),
                'voided_by' => $userId,
                'void_reason' => $reason,
            ])->save();
        });
    }
}
