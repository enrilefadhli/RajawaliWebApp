<?php

namespace App\Filament\Admin\Resources\SaleDetailResource\Pages;

use App\Filament\Admin\Resources\SaleDetailResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditSaleDetail extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = SaleDetailResource::class;
}
