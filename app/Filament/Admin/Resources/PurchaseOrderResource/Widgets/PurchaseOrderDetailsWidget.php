<?php

namespace App\Filament\Admin\Resources\PurchaseOrderResource\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PurchaseOrderDetailsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public ?PurchaseOrder $record = null;

    protected static ?string $heading = 'Details';

    public function table(Table $table): Table
    {
        return $table
            ->heading(static::$heading)
            ->query(
                PurchaseOrderDetail::query()->where('purchase_order_id', $this->record?->id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->actions([])
            ->bulkActions([])
            ->paginated(false);
    }
}
