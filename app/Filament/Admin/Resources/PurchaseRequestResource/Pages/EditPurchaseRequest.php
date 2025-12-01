<?php

namespace App\Filament\Admin\Resources\PurchaseRequestResource\Pages;

use App\Filament\Admin\Resources\PurchaseRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prevent changing requester; force to stored value
        $data['requested_by_id'] = $this->record->requested_by_id;

        // Approval can be set elsewhere (not via form), keep current
        $data['approved_by_id'] = $this->record->approved_by_id;

        return $data;
    }
}
