<?php

namespace App\Filament\Admin\Resources\SupplierResource\Pages;

use App\Filament\Admin\Resources\SupplierResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateSupplier extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = SupplierResource::class;
}
