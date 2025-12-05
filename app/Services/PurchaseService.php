<?php

namespace App\Services;

use App\Models\BatchOfStock;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseOrder;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Create a purchase (and batch stock) from a completed Purchase Order.
     * Purchases increase stock; no stock_adjustment is created.
     */
    public function createFromPurchaseOrder(PurchaseOrder $purchaseOrder, ?int $userId = null): Purchase
    {
        return DB::transaction(function () use ($purchaseOrder, $userId) {
            $purchaseOrder->loadMissing(['purchase', 'purchaseRequest', 'details.product']);

            if ($purchaseOrder->purchase) {
                return $purchaseOrder->purchase;
            }

            $userId = $userId ?? Auth::id();
            $supplierId = $purchaseOrder->purchaseRequest?->supplier_id;

            $total = $purchaseOrder->details->sum(function ($detail) {
                $price = (int) ($detail->product?->purchase_price ?? 0);
                return (int) $detail->quantity * $price;
            });

            $purchase = Purchase::create([
                'user_id' => $userId,
                'supplier_id' => $supplierId,
                'purchase_order_id' => $purchaseOrder->id,
                'total_amount' => $total,
                'notes' => null,
            ]);

            foreach ($purchaseOrder->details as $index => $detail) {
                $price = (int) ($detail->product?->purchase_price ?? 0);

                $purchaseDetail = PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $detail->product_id,
                    'quantity' => (int) $detail->quantity,
                    'price' => $price,
                ]);

                // Increase stock by creating a batch entry.
                BatchOfStock::create([
                    'product_id' => $purchaseDetail->product_id,
                    'quantity' => (int) $purchaseDetail->quantity,
                    'expiry_date' => null,
                    'batch_no' => null, // auto-generated
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Create a direct purchase (no PO/PR) with details and stock increase.
     *
     * @param array{
     *     supplier_id:int,
     *     notes?:string|null,
     *     purchase_order_id?:int|null,
     *     items?:array<array{product_id:int,quantity:int,price?:int|null}>
     * } $data
     */
    public function createDirectPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();
            $items = $data['items'] ?? [];

            $total = 0;
            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 0);
                $price = $item['price'] ?? Product::find($item['product_id'] ?? null)?->purchase_price ?? 0;
                $total += $qty * (int) $price;
            }

            $purchase = Purchase::create([
                'user_id' => $userId,
                'supplier_id' => $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'total_amount' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $price = (int) ($item['price'] ?? Product::find($productId)?->purchase_price ?? 0);

                $purchaseDetail = PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);

                BatchOfStock::create([
                    'product_id' => $purchaseDetail->product_id,
                    'quantity' => $purchaseDetail->quantity,
                    'expiry_date' => null,
                    'batch_no' => null,
                ]);
            }

            return $purchase;
        });
    }
}
