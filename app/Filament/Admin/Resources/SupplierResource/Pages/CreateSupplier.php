<?php

namespace App\Filament\Admin\Resources\SupplierResource\Pages;

use App\Filament\Admin\Resources\SupplierResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateSupplier extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = SupplierResource::class;
}
