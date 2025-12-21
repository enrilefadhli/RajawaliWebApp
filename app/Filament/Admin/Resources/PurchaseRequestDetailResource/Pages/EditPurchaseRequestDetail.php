<?php

namespace App\Filament\Admin\Resources\PurchaseRequestDetailResource\Pages;

use App\Filament\Admin\Resources\PurchaseRequestDetailResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditPurchaseRequestDetail extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseRequestDetailResource::class;
}
