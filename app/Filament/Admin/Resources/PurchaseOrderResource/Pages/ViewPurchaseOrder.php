<?php

namespace App\Filament\Admin\Resources\PurchaseOrderResource\Pages;

use App\Filament\Admin\Resources\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_po')
                ->label('Download PO')
                ->icon('heroicon-m-arrow-down-tray')
                ->action(fn () => PurchaseOrderResource::downloadPurchaseOrderPdf($this->record)),
            Action::make('cancel_po')
                ->label('Cancel PO')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status !== 'COMPLETED' && $this->record->status !== 'CANCELLED')
                ->form([
                    \Filament\Forms\Components\Textarea::make('cancel_reason')
                        ->label('Cancel reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'CANCELLED',
                        'cancel_reason' => $data['cancel_reason'] ?? null,
                        'cancelled_by' => auth()->user()?->getKey(),
                        'cancelled_at' => now(),
                    ]);

                    Notification::make()
                        ->title('PO cancelled')
                        ->success()
                        ->send();
                }),
        ];
    }
}
