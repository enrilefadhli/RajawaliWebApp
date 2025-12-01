<?php

namespace App\Filament\Admin\Resources\StockResource\Pages;

use App\Filament\Admin\Resources\StockResource;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\EditRecord;

class EditStock extends EditRecord
{
    protected static string $resource = StockResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }
}
