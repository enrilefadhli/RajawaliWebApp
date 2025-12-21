<?php

namespace App\Filament\Admin\Resources\CategoryResource\Pages;

use App\Filament\Admin\Resources\CategoryResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditCategory extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = CategoryResource::class;
}
