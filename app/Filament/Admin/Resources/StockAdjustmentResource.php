<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StockAdjustmentResource\Pages;
use App\Models\BatchOfStock;
use App\Models\StockAdjustment;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationGroup = 'Stock System';
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('adjustment_date')
                ->default(now())
                ->required(),
            Forms\Components\Select::make('reason')
                ->options([
                    'EXPIRED' => 'EXPIRED',
                    'DAMAGED' => 'DAMAGED',
                    'LOST' => 'LOST',
                    'FOUND' => 'FOUND',
                    'CORRECTION' => 'CORRECTION',
                ])
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
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (! $state) {
                                return;
                            }
                            $batch = BatchOfStock::with('product')->find($state);
                            if ($batch) {
                                $set('product_id', $batch->product_id);
                            }
                        })
                        ->required(),
                    Forms\Components\Hidden::make('product_id'),
                    Forms\Components\TextInput::make('qty_change')
                        ->label('Qty Change (+/-)')
                        ->numeric()
                        ->required(),
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
            ->defaultSort('adjustment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('adjustment_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('reason')->sortable(),
                Tables\Columns\TextColumn::make('creator.name')->label('Created By')->sortable(),
                Tables\Columns\TextColumn::make('items_sum_qty_change')
                    ->label('Total Change')
                    ->sum('items', 'qty_change')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reason')
                    ->options([
                        'EXPIRED' => 'EXPIRED',
                        'DAMAGED' => 'DAMAGED',
                        'LOST' => 'LOST',
                        'FOUND' => 'FOUND',
                        'CORRECTION' => 'CORRECTION',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'view' => Pages\ViewStockAdjustment::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessStockSystem() ?? false;
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canView($record): bool
    {
        return self::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }
}
