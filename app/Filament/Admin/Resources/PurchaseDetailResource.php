<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseDetailResource\Pages;
use App\Models\PurchaseDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseDetailResource extends Resource
{
    protected static ?string $model = PurchaseDetail::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessPurchasing() ?? false;
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canView($record): bool
    {
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return self::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('purchase_id')
                ->relationship('purchase', 'id')
                ->required()
                ->label('Purchase'),
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'product_name', fn ($query) => $query->where('status', 'ACTIVE'))
                ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->product_name} {$record->product_code}" . ($record->variant ? " ({$record->variant})" : '')))
                ->searchable(['product_name', 'product_code'])
                ->required(),
            Forms\Components\TextInput::make('quantity')->numeric()->required(),
            Forms\Components\TextInput::make('price')
                ->numeric()
                ->required()
                ->stripCharacters(',')
                ->mask(\Filament\Support\RawJs::make('$money($input)'))
                ->dehydrateStateUsing(fn ($state) => $state === null ? null : str_replace(',', '', (string) $state)),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchase.id')->label('Purchase')->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('price')->money('idr', true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
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
            'index' => Pages\ListPurchaseDetails::route('/'),
            'create' => Pages\CreatePurchaseDetail::route('/create'),
            'edit' => Pages\EditPurchaseDetail::route('/{record}/edit'),
        ];
    }
}
