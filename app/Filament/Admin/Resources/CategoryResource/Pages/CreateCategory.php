<?php

namespace App\Filament\Admin\Resources\CategoryResource\Pages;

use App\Filament\Admin\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateCategory extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = CategoryResource::class;
}
