<?php

namespace App\Filament\Admin\Resources\PurchaseOrderResource\Pages;

use App\Filament\Admin\Resources\PurchaseOrderResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchaseOrder extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseOrderResource::class;
}
