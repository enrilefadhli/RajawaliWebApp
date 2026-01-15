<?php

namespace App\Filament\Admin\Resources\SaleDetailResource\Pages;

use App\Filament\Admin\Resources\SaleDetailResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateSaleDetail extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = SaleDetailResource::class;
}
