<?php

namespace App\Filament\Admin\Resources\StockAdjustmentResource\Pages;

use App\Filament\Admin\Resources\StockAdjustmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
