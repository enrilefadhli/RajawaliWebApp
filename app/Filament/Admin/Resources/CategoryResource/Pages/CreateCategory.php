<?php

namespace App\Filament\Admin\Resources\CategoryResource\Pages;

use App\Filament\Admin\Resources\CategoryResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateCategory extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = CategoryResource::class;
}
