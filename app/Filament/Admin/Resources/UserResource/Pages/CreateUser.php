<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateUser extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = UserResource::class;
}
