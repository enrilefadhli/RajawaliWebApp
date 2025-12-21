<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Filament\Admin\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditRole extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = RoleResource::class;
}
