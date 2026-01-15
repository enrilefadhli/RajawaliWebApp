<?php

namespace App\Filament\Admin\Resources\PurchaseResource\Pages;

use App\Filament\Admin\Resources\PurchaseResource;
use App\Services\PurchaseService;
use Filament\Notifications\Notification;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchase extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseResource::class;

    protected function handleRecordCreation(array $data): \App\Models\Purchase
    {
        return app(PurchaseService::class)->createDirectPurchase($data);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Purchase created and stock increased')
            ->success()
            ->send();
    }
}
