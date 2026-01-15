<?php

namespace App\Filament\Admin\Resources\PurchaseRequestResource\Pages;

use App\Filament\Admin\Resources\PurchaseRequestResource;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchaseRequest extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by_id'] = Auth::user()?->getKey();
        $data['approved_by_id'] = null;
        $data['requested_at'] = $data['requested_at'] ?? now();

        $details = $data['details'] ?? [];
        $total = 0;
        foreach ($details as $index => $detail) {
            $qty = (int) ($detail['quantity'] ?? 0);
            $price = (int) ($detail['expected_unit_price'] ?? Product::find($detail['product_id'] ?? null)?->purchase_price ?? 0);
            $details[$index]['expected_unit_price'] = $price;
            $total += $qty * $price;
        }

        $data['details'] = $details;
        $data['total_expected_amount'] = $total;

        return $data;
    }
}
