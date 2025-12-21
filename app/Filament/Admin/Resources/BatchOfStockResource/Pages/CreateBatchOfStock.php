<?php

namespace App\Filament\Admin\Resources\BatchOfStockResource\Pages;

use App\Filament\Admin\Resources\BatchOfStockResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateBatchOfStock extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = BatchOfStockResource::class;
}
