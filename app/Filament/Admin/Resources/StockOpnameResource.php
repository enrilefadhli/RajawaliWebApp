<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StockOpnameResource\Pages;
use App\Models\BatchOfStock;
use App\Models\StockOpname;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationGroup = 'Stock';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('opname_date')
                ->default(now())
                ->required(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
            Repeater::make('items')
                ->relationship()
                ->schema([
                    Forms\Components\Select::make('batch_of_stock_id')
                        ->label('Batch')
                        ->relationship('batch', 'batch_no')
                        ->getOptionLabelFromRecordUsing(fn (BatchOfStock $record) => "{$record->product->product_name} | Batch: {$record->batch_no} | Exp: {$record->expiry_date} | Qty: {$record->quantity}")
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set, $get) {
                            if (! $state) {
                                return;
                            }
                            $batch = BatchOfStock::with('product')->find($state);
                            if (! $batch) {
                                return;
                            }
                            $set('product_id', $batch->product_id);
                            $set('system_qty', $batch->quantity);
                            $set('difference_qty', ((int) $get('actual_qty')) - $batch->quantity);
                        })
                        ->required(),
                    Forms\Components\Hidden::make('product_id'),
                    Forms\Components\TextInput::make('system_qty')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true),
                    Forms\Components\TextInput::make('actual_qty')
                        ->numeric()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn ($state, Set $set, $get) => $set('difference_qty', ((int) $state) - ((int) $get('system_qty')))),
                    Forms\Components\TextInput::make('difference_qty')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])
                ->minItems(1)
                ->columns(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('opname_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('opname_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('creator.name')->label('Created By')->sortable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Items')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('opname_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('opname_date', '>=', $date))
                        ->when($data['to'] ?? null, fn ($q, $date) => $q->whereDate('opname_date', '<=', $date))),
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
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'view' => Pages\ViewStockOpname::route('/{record}'),
            'edit' => Pages\EditStockOpname::route('/{record}/edit'),
        ];
    }
}
