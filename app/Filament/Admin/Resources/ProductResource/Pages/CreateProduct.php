<?php

namespace App\Filament\Admin\Resources\ProductResource\Pages;

use App\Filament\Admin\Resources\ProductResource;
use App\Services\InventoryService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $stock = (int) ($this->data['current_stock'] ?? 0);

        app(InventoryService::class)->setInitialStock(
            $this->record,
            $stock,
            Auth::user()
        );

        Notification::make()
            ->title('Product created')
            ->body('Stock initialized to ' . $stock)
            ->success()
            ->send();
    }
}
