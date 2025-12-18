<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SaleDetailResource\Pages;
use App\Models\SaleDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaleDetailResource extends Resource
{
    protected static ?string $model = SaleDetail::class;

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('sale_id')
                ->relationship('sale', 'id')
                ->required()
                ->label('Sale'),
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'product_name', fn ($query) => $query->where('status', 'ACTIVE'))
                ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->product_name . ($record->variant ? " ({$record->variant})" : '')))
                ->required(),
            Forms\Components\TextInput::make('quantity')->numeric()->required(),
            Forms\Components\TextInput::make('price')->numeric()->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale.id')->label('Sale')->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('price')->money('idr', true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('sale_date')
                    ->label('Sale Date')
                    ->default(['date' => today()])
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('Select date')
                            ->default(today()),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['date'] ?? null,
                            fn ($q, $date) => $q->whereHas('sale', fn ($sq) => $sq->whereDate('sale_date', $date))
                        );
                    }),
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
            'index' => Pages\ListSaleDetails::route('/'),
            'create' => Pages\CreateSaleDetail::route('/create'),
            'edit' => Pages\EditSaleDetail::route('/{record}/edit'),
        ];
    }
}
