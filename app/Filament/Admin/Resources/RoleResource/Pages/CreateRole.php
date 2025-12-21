<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Filament\Admin\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateRole extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = RoleResource::class;
}
