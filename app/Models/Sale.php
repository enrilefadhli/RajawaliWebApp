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

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'total_amount',
        'status',
        'sale_date',
        'notes',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'voided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            if (empty($sale->code)) {
                $sale->code = app(\App\Services\CodeGeneratorService::class)->generate(
                    prefix: 'S-',
                    table: $sale->getTable(),
                    column: 'code',
                    date: $sale->sale_date ?? $sale->created_at ?? now(),
                    pad: 5
                );
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function voidSale(?int $userId, ?string $reason = null): void
    {
        if ($this->status === 'VOIDED') {
            throw ValidationException::withMessages([
                'status' => 'Sale is already voided.',
            ]);
        }

        DB::transaction(function () use ($userId, $reason) {
            $movements = StockMovement::query()
                ->where('movement_type', 'SALE')
                ->where('direction', 'OUT')
                ->where('source_type', 'sale')
                ->where('source_id', $this->id)
                ->lockForUpdate()
                ->get();

            foreach ($movements as $movement) {
                if (! $movement->batch_of_stock_id) {
                    continue;
                }

                $batch = BatchOfStock::lockForUpdate()->find($movement->batch_of_stock_id);
                if (! $batch) {
                    continue;
                }

                $stockBefore = (int) $batch->quantity;
                $stockAfter = $stockBefore + (int) $movement->quantity;

                $batch->update(['quantity' => $stockAfter]);

                StockMovement::create([
                    'product_id' => $batch->product_id,
                    'batch_of_stock_id' => $batch->id,
                    'movement_type' => 'SALE',
                    'direction' => 'IN',
                    'quantity' => (int) $movement->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'source_type' => 'sale_void',
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

    public function recalculateTotal(): void
    {
        $total = $this->details()
            ->select(DB::raw('SUM(price * quantity) as total'))
            ->value('total') ?? 0;

        $this->forceFill(['total_amount' => $total])->saveQuietly();
    }
}
