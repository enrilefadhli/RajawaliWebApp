<?php

namespace App\Filament\Admin\Resources\PurchaseOrderDetailResource\Pages;

use App\Filament\Admin\Resources\PurchaseOrderDetailResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchaseOrderDetail extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseOrderDetailResource::class;
}
