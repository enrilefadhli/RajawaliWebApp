<?php

namespace App\Filament\Admin\Resources\PurchaseDetailResource\Pages;

use App\Filament\Admin\Resources\PurchaseDetailResource;
use App\Filament\Admin\Pages\BaseCreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchaseDetail extends BaseCreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseDetailResource::class;
}
