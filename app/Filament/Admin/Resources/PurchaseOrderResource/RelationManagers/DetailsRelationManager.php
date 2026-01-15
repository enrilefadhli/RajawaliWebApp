<?php

namespace App\Filament\Admin\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Product;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'product_name', fn ($query) => $query->whereIn('status', ['ACTIVE', 'STORED']))
                ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->product_name} {$record->product_code}" . ($record->variant ? " ({$record->variant})" : '')))
                ->searchable(['product_name', 'product_code'])
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $product = Product::find($state);
                    $price = (int) ($product?->purchase_price ?? 0);
                    $set('unit_price', $price);
                    $set('expected_unit_price', $price);
                })
                ->disabled()
                ->required()
                ->preload()
                ->label('Product'),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->minValue(1)
                ->required()
                ->disabled(),
            Forms\Components\DatePicker::make('expiry_date')
                ->label('Expiry Date')
                ->required()
                ->native(false),
            Forms\Components\TextInput::make('expected_unit_price')
                ->label('Expected Unit Price')
                ->disabled()
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->stripCharacters(',')
                ->mask(\Filament\Support\RawJs::make('$money($input)'))
                ->dehydrateStateUsing(fn ($state) => $state === null ? null : str_replace(',', '', (string) $state)),
            Forms\Components\TextInput::make('unit_price')
                ->label('Unit Price')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->reactive()
                ->stripCharacters(',')
                ->mask(\Filament\Support\RawJs::make('$money($input)'))
                ->dehydrateStateUsing(fn ($state) => $state === null ? null : str_replace(',', '', (string) $state))
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('unit_price', $state);
                })
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('expiry_date')->date()->label('Expiry Date'),
                Tables\Columns\TextColumn::make('expected_unit_price')->money('idr', true)->label('Expected Unit Price'),
                Tables\Columns\TextColumn::make('unit_price')->money('idr', true)->label('Unit Price'),
                Tables\Columns\TextColumn::make('line_total')
                    ->label('Line Total')
                    ->money('idr', true)
                    ->state(fn ($record) => (float) ($record->unit_price ?? 0) * (int) ($record->quantity ?? 0)),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Unit Price')
                    ->modalButton('Save')
                    ->form([
                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Expiry Date')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->stripCharacters(',')
                            ->mask(\Filament\Support\RawJs::make('$money($input)'))
                            ->dehydrateStateUsing(fn ($state) => $state === null ? null : str_replace(',', '', (string) $state))
                            ->required(),
                    ]),
            ])
            ->bulkActions([]);
    }
}
