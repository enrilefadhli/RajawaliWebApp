<?php

namespace App\Filament\Admin\Resources\SaleResource\Pages;

use App\Filament\Admin\Resources\SaleResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditSale extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = SaleResource::class;
}
