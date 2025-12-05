<?php

namespace App\Filament\Admin\Resources\PurchaseOrderResource\Pages;

use App\Filament\Admin\Resources\PurchaseOrderResource;
use App\Filament\Admin\Resources\PurchaseOrderResource\Widgets\PurchaseOrderDetailsWidget;
use App\Services\PurchaseService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

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
        $data['status'] = 'COMPLETED';

        return $data;
    }

    protected function afterSave(): void
    {
        app(PurchaseService::class)->createFromPurchaseOrder($this->record, auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PurchaseOrderDetailsWidget::class,
        ];
    }
}
