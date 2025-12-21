<?php

namespace App\Filament\Admin\Resources\BatchOfStockResource\Pages;

use App\Filament\Admin\Resources\BatchOfStockResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditBatchOfStock extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = BatchOfStockResource::class;
}
