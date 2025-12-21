<?php

namespace App\Filament\Admin\Resources\PurchaseOrderDetailResource\Pages;

use App\Filament\Admin\Resources\PurchaseOrderDetailResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditPurchaseOrderDetail extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseOrderDetailResource::class;
}
