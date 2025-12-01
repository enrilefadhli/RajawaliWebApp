<?php

namespace App\Filament\Admin\Resources\StockOpnameSessionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OpnameRowsRelationManager extends RelationManager
{
    protected static string $relationship = 'opnameRows';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'product_name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Product'),
            Forms\Components\TextInput::make('stock_system')
                ->numeric()
                ->minValue(0)
                ->required(),
            Forms\Components\TextInput::make('stock_physical')
                ->numeric()
                ->minValue(0)
                ->required()
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('difference', ((int) $state) - ((int) $get('stock_system')))),
            Forms\Components\TextInput::make('difference')
                ->numeric()
                ->disabled()
                ->dehydrated(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable(),
                Tables\Columns\TextColumn::make('stock_system'),
                Tables\Columns\TextColumn::make('stock_physical'),
                Tables\Columns\TextColumn::make('difference'),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
