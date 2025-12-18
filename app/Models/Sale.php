<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'sale_date',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function recalculateTotal(): void
    {
        $total = $this->details()
            ->select(DB::raw('SUM(price * quantity) as total'))
            ->value('total') ?? 0;

        $this->forceFill(['total_amount' => $total])->saveQuietly();
    }
}
