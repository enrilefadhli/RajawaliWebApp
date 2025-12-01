<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function setInitialStock(Product $product, int $quantity = 0, ?User $user = null): void
    {
        $quantity = max(0, $quantity);

        $product->stock()->firstOrCreate(
            [],
            [
                'quantity' => $quantity,
                'updated_by' => $user?->id,
            ]
        );
    }

    public function updateStockManual(Product $product, int $newStock, ?User $user = null, string $reason = 'Manual update'): void
    {
        $newStock = max(0, $newStock);

        DB::transaction(function () use ($product, $newStock, $user, $reason) {
            /** @var Stock $stock */
            $stock = $product->stock()->lockForUpdate()->firstOrCreate(
                [],
                [
                    'quantity' => 0,
                    'updated_by' => $user?->id,
                ]
            );

            $oldStock = $stock->quantity;

            if ($oldStock === $newStock) {
                return;
            }

            StockAdjustment::create([
                'product_id' => $product->id,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'difference' => $newStock - $oldStock,
                'reason' => $reason,
                'created_by' => $user?->id,
            ]);

            $stock->update([
                'quantity' => $newStock,
                'updated_by' => $user?->id,
            ]);
        });
    }
}
