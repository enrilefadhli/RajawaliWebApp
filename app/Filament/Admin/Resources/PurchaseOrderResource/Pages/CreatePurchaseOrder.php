<?php

namespace App\Filament\Admin\Resources\PurchaseOrderResource\Pages;

use App\Filament\Admin\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchaseOrder extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseOrderResource::class;
}
