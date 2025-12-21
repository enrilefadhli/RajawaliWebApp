<?php

namespace App\Filament\Admin\Resources\PurchaseRequestDetailResource\Pages;

use App\Filament\Admin\Resources\PurchaseRequestDetailResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchaseRequestDetail extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseRequestDetailResource::class;
}
