<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('Category')
                ->relationship('category', 'category_name')
                ->required(),
            Forms\Components\TextInput::make('product_code')->required()->maxLength(255),
            Forms\Components\TextInput::make('sku')->maxLength(255),
            Forms\Components\TextInput::make('product_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('variant')->maxLength(255),
            Forms\Components\TextInput::make('purchase_price')->numeric()->required(),
            Forms\Components\TextInput::make('selling_price')->numeric()->required(),
            Forms\Components\TextInput::make('discount_percent')->numeric()->nullable(),
            Forms\Components\TextInput::make('discount_amount')->numeric()->nullable(),
            Forms\Components\TextInput::make('minimum_stock')->numeric()->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('product_code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('sku')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('product_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('variant')->searchable(),
                Tables\Columns\TextColumn::make('category.category_name')->label('Category')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('selling_price')->money('idr', true)->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')->money('idr', true)->sortable(),
                Tables\Columns\TextColumn::make('minimum_stock'),
                Tables\Columns\TextColumn::make('batchOfStocks_sum_quantity')
                    ->label('Total Stock')
                    ->sum('batchOfStocks', 'quantity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'category_name'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
