<?php

namespace App\Filament\Admin\Resources\StockAdjustmentResource\Pages;

use App\Filament\Admin\Resources\StockAdjustmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateStockAdjustment extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = StockAdjustmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::user()?->getKey();

        return $data;
    }
}
