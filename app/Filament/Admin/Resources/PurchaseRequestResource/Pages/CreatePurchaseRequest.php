<?php

namespace App\Filament\Admin\Resources\PurchaseRequestResource\Pages;

use App\Filament\Admin\Resources\PurchaseRequestResource;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by_id'] = Auth::user()?->getKey();
        $data['approved_by_id'] = null;
        $data['requested_at'] = $data['requested_at'] ?? now();

        $details = $data['details'] ?? [];
        $total = 0;
        foreach ($details as $detail) {
            $qty = (int) ($detail['quantity'] ?? 0);
            $price = (int) (Product::find($detail['product_id'] ?? null)?->purchase_price ?? 0);
            $total += $qty * $price;
        }

        $data['total_amount'] = $total;

        return $data;
    }
}
