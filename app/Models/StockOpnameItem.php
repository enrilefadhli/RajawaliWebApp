<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'batch_of_stock_id',
        'product_id',
        'system_qty',
        'actual_qty',
        'difference_qty',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (StockOpnameItem $item) {
            $item->difference_qty = (int) $item->actual_qty - (int) $item->system_qty;

            if ($item->batch_of_stock_id && ! $item->product_id) {
                $batch = BatchOfStock::find($item->batch_of_stock_id);
                if ($batch) {
                    $item->product_id = $batch->product_id;
                }
            }
        });
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
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
