<?php

namespace App\Filament\Admin\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class BaseCreateRecord extends CreateRecord
{
    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Created successfully')
            ->success()
            ->send();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
