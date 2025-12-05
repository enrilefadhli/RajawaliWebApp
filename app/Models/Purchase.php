<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'supplier_id',
        'purchase_order_id',
        'total_amount',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Purchase $purchase): void {
            if (empty($purchase->code)) {
                $purchase->code = app(\App\Services\CodeGeneratorService::class)->generate(
                    prefix: 'DP-',
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
}
