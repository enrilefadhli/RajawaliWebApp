<?php

namespace App\Filament\Admin\Resources\PurchaseDetailResource\Pages;

use App\Filament\Admin\Resources\PurchaseDetailResource;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Admin\Concerns\RedirectsToIndex;

class EditPurchaseDetail extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = PurchaseDetailResource::class;
}
