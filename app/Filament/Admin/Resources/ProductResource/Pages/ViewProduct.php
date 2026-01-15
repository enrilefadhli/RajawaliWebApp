<?php

namespace App\Filament\Admin\Resources\ProductResource\Pages;

use App\Filament\Admin\Resources\BatchOfStockResource;
use App\Filament\Admin\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_batch')
                ->label('Add Batch')
                ->icon('heroicon-o-plus')
                ->url(fn () => BatchOfStockResource::getUrl('create', ['product_id' => $this->record->id])),
            Actions\EditAction::make(),
        ];
    }
}
