<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\CodeGeneratorService;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'purchase_request_id',
        'status',
        'attachment_path',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $purchaseOrder): void {
            if (empty($purchaseOrder->code)) {
                $requestedAt = $purchaseOrder->purchaseRequest?->requested_at;

                if (! $requestedAt && $purchaseOrder->purchase_request_id) {
                    $requestedAt = $purchaseOrder->purchaseRequest()->value('requested_at');
                }

                $purchaseOrder->code = app(CodeGeneratorService::class)->generate(
                    prefix: 'PO-',
                    table: $purchaseOrder->getTable(),
                    column: 'code',
                    date: $requestedAt
                );
            }
        });
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class);
    }
}
