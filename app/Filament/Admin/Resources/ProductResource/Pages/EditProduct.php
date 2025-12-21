<?php

namespace App\Filament\Admin\Resources\ProductResource\Pages;

use App\Filament\Admin\Resources\ProductResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditProduct extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = ProductResource::class;
}
