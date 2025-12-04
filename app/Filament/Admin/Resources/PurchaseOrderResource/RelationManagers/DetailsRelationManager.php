<?php

namespace App\Filament\Admin\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'product_name', fn ($query) => $query->whereIn('status', ['ACTIVE', 'STORED']))
                ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->product_name . ($record->variant ? " ({$record->variant})" : '')))
                ->required()
                ->searchable()
                ->preload()
                ->label('Product'),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->minValue(1)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
