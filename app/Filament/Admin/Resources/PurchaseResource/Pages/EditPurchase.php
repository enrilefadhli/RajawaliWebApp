<?php

namespace App\Filament\Admin\Resources\PurchaseResource\Pages;

use App\Filament\Admin\Resources\PurchaseResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditPurchase extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseResource::class;
}
