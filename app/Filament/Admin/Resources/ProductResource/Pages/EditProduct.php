<?php

namespace App\Filament\Admin\Resources\ProductResource\Pages;

use App\Filament\Admin\Resources\ProductResource;
use App\Services\InventoryService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterSave(): void
    {
        $newStock = (int) ($this->data['current_stock'] ?? 0);

        app(InventoryService::class)->updateStockManual(
            $this->record,
            $newStock,
            Auth::user(),
            'Manual update from Product form'
        );

        Notification::make()
            ->title('Product updated')
            ->body('Stock adjusted to ' . $newStock)
            ->success()
            ->send();
    }
}
