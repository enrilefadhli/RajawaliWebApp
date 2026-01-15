<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationGroup = 'Stock System';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessStockSystem() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\BadgeColumn::make('movement_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'PURCHASE',
                        'danger' => 'SALE',
                        'warning' => 'ADJUSTMENT',
                        'info' => 'OPNAME',
                    ])
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('direction')
                    ->colors([
                        'success' => 'IN',
                        'danger' => 'OUT',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch.batch_no')
                    ->label('Batch')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('quantity')->sortable(),
                Tables\Columns\TextColumn::make('stock_before')->label('Before')->sortable(),
                Tables\Columns\TextColumn::make('stock_after')->label('After')->sortable(),
                Tables\Columns\TextColumn::make('source_type')
                    ->label('Source')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('source_id')
                    ->label('Source ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('User')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')
                    ->options([
                        'PURCHASE' => 'Purchase',
                        'SALE' => 'Sale',
                        'ADJUSTMENT' => 'Adjustment',
                        'OPNAME' => 'Opname',
                    ]),
                Tables\Filters\SelectFilter::make('direction')
                    ->options([
                        'IN' => 'In',
                        'OUT' => 'Out',
                    ]),
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'product_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->product_name} {$record->product_code}" . ($record->variant ? " ({$record->variant})" : '')))
                    ->searchable(['product_name', 'product_code']),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
                            ->when($data['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to));
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }
}
