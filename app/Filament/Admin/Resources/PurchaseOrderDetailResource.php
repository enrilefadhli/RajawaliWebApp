<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseOrderDetailResource\Pages;
use App\Models\PurchaseOrderDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderDetailResource extends Resource
{
    protected static ?string $model = PurchaseOrderDetail::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('purchase_order_id')
                ->relationship('purchaseOrder', 'id')
                ->required()
                ->label('Purchase Order'),
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'product_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->product_name . ($record->variant ? " ({$record->variant})" : '')))
                ->required(),
            Forms\Components\TextInput::make('quantity')->numeric()->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchaseOrder.id')->label('PO')->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrderDetails::route('/'),
            'create' => Pages\CreatePurchaseOrderDetail::route('/create'),
            'edit' => Pages\EditPurchaseOrderDetail::route('/{record}/edit'),
        ];
    }
}
