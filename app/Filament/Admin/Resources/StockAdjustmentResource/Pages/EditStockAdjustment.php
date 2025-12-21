<?php

namespace App\Filament\Admin\Resources\StockAdjustmentResource\Pages;

use App\Filament\Admin\Resources\StockAdjustmentResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditStockAdjustment extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = StockAdjustmentResource::class;
}
