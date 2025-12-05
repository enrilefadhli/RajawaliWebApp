<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\PurchaseRequestApproval;
use App\Services\CodeGeneratorService;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'requested_by_id',
        'approved_by_id',
        'supplier_id',
        'total_expected_amount',
        'request_note',
        'status',
        'requested_at',
        'handled_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'handled_at' => 'datetime',
        'total_expected_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseRequest $purchaseRequest): void {
            if (empty($purchaseRequest->code)) {
                $purchaseRequest->code = app(CodeGeneratorService::class)->generate(
                    prefix: 'PR-',
                    table: $purchaseRequest->getTable(),
                    column: 'code',
                    date: $purchaseRequest->requested_at
                );
            }
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseRequestDetail::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseRequestApproval::class);
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    /**
     * Fallback to detail sums if the stored total is missing/zero.
     */
    public function getTotalExpectedAmountAttribute($value): float
    {
        $numeric = $value !== null && $value !== '' ? (float) $value : 0.0;
        if ($numeric > 0) {
            return $numeric;
        }

        $sum = $this->details()
            ->selectRaw('COALESCE(SUM(quantity * expected_unit_price), 0) as total')
            ->value('total');

        return (float) $sum;
    }
}
