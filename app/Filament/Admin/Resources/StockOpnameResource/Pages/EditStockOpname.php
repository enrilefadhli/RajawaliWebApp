<?php

namespace App\Filament\Admin\Resources\StockOpnameResource\Pages;

use App\Filament\Admin\Resources\StockOpnameResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditStockOpname extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = StockOpnameResource::class;
}
