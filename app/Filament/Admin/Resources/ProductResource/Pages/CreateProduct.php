<?php

namespace App\Filament\Admin\Resources\ProductResource\Pages;

use App\Filament\Admin\Resources\ProductResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateProduct extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['product_code'])) {
            $data['product_code'] = ProductResource::generateProductCodeByCategory($data['category_id'] ?? null);
        }

        return $data;
    }
}
