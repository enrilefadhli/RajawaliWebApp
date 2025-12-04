<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
