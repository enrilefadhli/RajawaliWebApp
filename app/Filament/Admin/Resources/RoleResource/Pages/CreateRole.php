<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Filament\Admin\Resources\RoleResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateRole extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = RoleResource::class;
}
