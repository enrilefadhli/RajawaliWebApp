<?php

namespace App\Filament\Admin\Resources\SaleDetailResource\Pages;

use App\Filament\Admin\Resources\SaleDetailResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateSaleDetail extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = SaleDetailResource::class;
}
