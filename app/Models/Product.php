<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\BatchOfStock;
use App\Models\StockOpnameItem;
use App\Models\StockAdjustmentItem;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_code',
        'sku',
        'product_name',
        'variant',
        'purchase_price',
        'selling_price',
        'discount_percent',
        'discount_amount',
        'minimum_stock',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeStored($query)
    {
        return $query->where('status', 'STORED');
    }

    public function scopeDisabled($query)
    {
        return $query->where('status', 'DISABLED');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function purchaseRequestDetails(): HasMany
    {
        return $this->hasMany(PurchaseRequestDetail::class);
    }

    public function purchaseOrderDetails(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function batchOfStocks(): HasMany
    {
        return $this->hasMany(BatchOfStock::class, 'product_id');
    }

    public function stockOpnameItems(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function stockAdjustmentItems(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function effectiveSellingPrice(): float
    {
        $price = (float) ($this->selling_price ?? 0);
        $discountPercent = $this->discount_percent === null ? null : (float) $this->discount_percent;
        $discountAmount = $this->discount_amount === null ? null : (float) $this->discount_amount;

        if ($discountPercent !== null && $discountPercent > 0) {
            $discountPercent = max(0, min(100, $discountPercent));
            $discount = $price * ($discountPercent / 100);
        } else {
            $discountAmount = $discountAmount ?? 0;
            $discount = max(0, min($price, $discountAmount));
        }

        $final = $price - $discount;

        return $final < 0 ? 0 : $final;
    }

    protected function availableStock(): Attribute
    {
        return Attribute::get(function () {
            if (array_key_exists('available_stock_sum', $this->attributes)) {
                return (int) ($this->attributes['available_stock_sum'] ?? 0);
            }

            if ($this->relationLoaded('batchOfStocks')) {
                return (int) $this->batchOfStocks
                    ->filter(function (BatchOfStock $batch) {
                        if (($batch->quantity ?? 0) <= 0) {
                            return false;
                        }

                        if (! $batch->expiry_date) {
                            return true;
                        }

                        return $batch->expiry_date->toDateString() >= now()->toDateString();
                    })
                    ->sum('quantity');
            }

            return (int) $this->batchOfStocks()
                ->where('quantity', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
                })
                ->sum('quantity');
        });
    }
}
