<?php

namespace App\Filament\Admin\Resources\ProductResource\Pages;

use App\Filament\Admin\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateProduct extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = ProductResource::class;
}
