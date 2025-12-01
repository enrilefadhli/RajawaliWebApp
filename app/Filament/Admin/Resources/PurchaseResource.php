<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseResource\Pages;
use App\Filament\Admin\Resources\PurchaseResource\RelationManagers\DetailsRelationManager;
use App\Models\Purchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationGroup = 'Purchasing';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 42;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Handled By'),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'supplier_name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('purchase_order_id')
                ->relationship('purchaseOrder', 'id')
                ->searchable()
                ->preload()
                ->label('Purchase Order'),
            Forms\Components\TextInput::make('total_amount')->numeric()->required(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Handled By')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('supplier.supplier_name')->label('Supplier')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('purchaseOrder.id')->label('PO'),
                Tables\Columns\TextColumn::make('total_amount')->money('idr', true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'supplier_name'),
                Tables\Filters\TernaryFilter::make('has_po')
                    ->label('Has PO')
                    ->trueLabel('With PO')
                    ->falseLabel('Without PO')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('purchase_order_id'),
                        false: fn ($query) => $query->whereNull('purchase_order_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'view' => Pages\ViewPurchase::route('/{record}'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
        ];
    }
}
