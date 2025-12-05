<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseRequestDetailResource\Pages;
use App\Models\PurchaseRequestDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseRequestDetailResource extends Resource
{
    protected static ?string $model = PurchaseRequestDetail::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('purchase_request_id')
                ->relationship('purchaseRequest', 'id')
                ->required()
                ->label('Purchase Request'),
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'product_name', fn ($query) => $query->where('status', 'ACTIVE'))
                ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->product_name . ($record->variant ? " ({$record->variant})" : '')))
                ->required(),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('expected_unit_price')
                ->label('Expected Unit Price')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchaseRequest.id')->label('PR')->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('expected_unit_price')->money('idr', true)->label('Expected Unit Price'),
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
            'index' => Pages\ListPurchaseRequestDetails::route('/'),
            'create' => Pages\CreatePurchaseRequestDetail::route('/create'),
            'edit' => Pages\EditPurchaseRequestDetail::route('/{record}/edit'),
        ];
    }
}
