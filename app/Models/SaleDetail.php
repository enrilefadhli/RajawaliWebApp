<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use App\Models\BatchOfStock;

class SaleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'price',
    ];

    protected static function booted(): void
    {
        static::creating(function (SaleDetail $detail) {
            if (! $detail->product_id) {
                throw ValidationException::withMessages([
                    'product_id' => 'Product is required for stock deduction.',
                ]);
            }

            BatchOfStock::deductFefo($detail->product_id, (int) $detail->quantity);
        });

        static::created(function (SaleDetail $detail) {
            $detail->sale?->recalculateTotal();
        });

        static::updated(function (SaleDetail $detail) {
            $detail->sale?->recalculateTotal();
        });

        static::deleted(function (SaleDetail $detail) {
            $detail->sale?->recalculateTotal();
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
