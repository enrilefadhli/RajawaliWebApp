<?php

namespace App\Filament\Admin\Resources\BatchOfStockResource\Pages;

use App\Filament\Admin\Resources\BatchOfStockResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateBatchOfStock extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = BatchOfStockResource::class;
}
