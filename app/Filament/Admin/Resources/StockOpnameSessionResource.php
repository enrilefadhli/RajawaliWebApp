<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StockOpnameSessionResource\Pages;
use App\Filament\Admin\Resources\StockOpnameSessionResource\RelationManagers\OpnameRowsRelationManager;
use App\Models\StockOpnameSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockOpnameSessionResource extends Resource
{
    protected static ?string $model = StockOpnameSession::class;

    protected static ?string $navigationGroup = 'Stock';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 32;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('created_by')
                ->relationship('creator', 'name')
                ->default(fn () => auth()->id())
                ->disabled()
                ->dehydrated(false)
                ->label('Created By'),
            Forms\Components\DatePicker::make('opname_date')->required(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('opname_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('creator.name')->label('Created By')->sortable(),
                Tables\Columns\TextColumn::make('opname_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('notes')->limit(40),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('created_by')
                    ->relationship('creator', 'name')
                    ->label('Created By'),
                Tables\Filters\Filter::make('opname_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('opname_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('opname_date', '<=', $date));
                    }),
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
            'index' => Pages\ListStockOpnameSessions::route('/'),
            'create' => Pages\CreateStockOpnameSession::route('/create'),
            'view' => Pages\ViewStockOpnameSession::route('/{record}'),
            'edit' => Pages\EditStockOpnameSession::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            OpnameRowsRelationManager::class,
        ];
    }
}
