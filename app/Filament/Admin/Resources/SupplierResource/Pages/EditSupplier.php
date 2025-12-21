<?php

namespace App\Filament\Admin\Resources\SupplierResource\Pages;

use App\Filament\Admin\Resources\SupplierResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditSupplier extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = SupplierResource::class;
}
