<?php

namespace App\Filament\Admin\Resources\PurchaseRequestDetailResource\Pages;

use App\Filament\Admin\Resources\PurchaseRequestDetailResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchaseRequestDetail extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseRequestDetailResource::class;
}
