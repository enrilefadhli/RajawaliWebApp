<?php

namespace App\Filament\Admin\Resources\BatchOfStockResource\Pages;

use App\Filament\Admin\Resources\BatchOfStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatchOfStocks extends ListRecords
{
    protected static string $resource = BatchOfStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
