<?php

namespace App\Filament\Admin\Resources\SaleResource\Pages;

use App\Filament\Admin\Resources\SaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $details = collect($data['details'] ?? []);

        $data['user_id'] = auth()->user()?->getKey();
        $data['sale_date'] = $data['sale_date'] ?? now();
        $data['total_amount'] = $details->sum(function ($item) {
            return (float) Arr::get($item, 'price', 0) * (int) Arr::get($item, 'quantity', 0);
        });

        return $data;
    }
}
