<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('supplier_code')->maxLength(255),
            Forms\Components\TextInput::make('supplier_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('supplier_phone')->maxLength(255),
            Forms\Components\TextInput::make('supplier_address')->maxLength(255),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('supplier_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier_phone'),
                Tables\Columns\TextColumn::make('supplier_address'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
