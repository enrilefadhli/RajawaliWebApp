<?php

namespace App\Filament\Admin\Resources\PurchaseDetailResource\Pages;

use App\Filament\Admin\Resources\PurchaseDetailResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class CreatePurchaseDetail extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseDetailResource::class;
}
