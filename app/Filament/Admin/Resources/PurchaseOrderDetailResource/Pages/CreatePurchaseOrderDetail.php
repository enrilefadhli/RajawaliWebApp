<?php

namespace App\Filament\Admin\Resources\PurchaseOrderDetailResource\Pages;

use App\Filament\Admin\Resources\PurchaseOrderDetailResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchaseOrderDetail extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseOrderDetailResource::class;
}
