<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreateUser extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = UserResource::class;
}
