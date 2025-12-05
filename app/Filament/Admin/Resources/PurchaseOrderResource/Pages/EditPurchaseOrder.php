<?php

namespace App\Filament\Admin\Resources\PurchaseOrderResource\Pages;

use App\Filament\Admin\Resources\PurchaseOrderResource;
use App\Filament\Admin\Resources\PurchaseOrderResource\Widgets\PurchaseOrderDetailsWidget;
use App\Services\PurchaseService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Update Status');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $missingExpiry = $this->record->details()->whereNull('expiry_date')->count();
        if ($missingExpiry > 0) {
            throw ValidationException::withMessages([
                'details' => 'Please set an expiry date for all items before completing this PO.',
            ]);
        }

        $data['status'] = 'COMPLETED';
        // total_amount will be recalculated after save based on detail unit prices.

        return $data;
    }

    protected function afterSave(): void
    {
        try {
            $total = $this->record->details()->selectRaw('COALESCE(SUM(quantity * unit_price),0) as total')->value('total') ?? 0;
            $this->record->update(['total_amount' => $total]);

            $userId = auth()->user()?->getKey();
            app(PurchaseService::class)->createFromPurchaseOrder(
                $this->record,
                $userId !== null ? (int) $userId : null
            );
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Unable to complete PO')
                ->body(collect($e->errors())->flatten()->implode(' '))
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
