<?php

namespace App\Filament\Admin\Resources\StockOpnameSessionResource\Pages;

use App\Filament\Admin\Resources\StockOpnameSessionResource;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;

class CreateStockOpnameSession extends CreateRecord
{
    protected static string $resource = StockOpnameSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
