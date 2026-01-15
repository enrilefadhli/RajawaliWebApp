<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseRequestDetailResource\Pages;
use App\Models\PurchaseRequestDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseRequestDetailResource extends Resource
{
    protected static ?string $model = PurchaseRequestDetail::class;

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
            Forms\Components\Select::make('purchase_request_id')
                ->relationship('purchaseRequest', 'id')
                ->required()
                ->label('Purchase Request'),
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'product_name', fn ($query) => $query->where('status', 'ACTIVE'))
                ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->product_name} {$record->product_code}" . ($record->variant ? " ({$record->variant})" : '')))
                ->searchable(['product_name', 'product_code'])
                ->required(),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('expected_unit_price')
                ->label('Expected Unit Price')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
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
                Tables\Columns\TextColumn::make('purchaseRequest.id')->label('PR')->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('expected_unit_price')->money('idr', true)->label('Expected Unit Price'),
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
            'index' => Pages\ListPurchaseRequestDetails::route('/'),
            'create' => Pages\CreatePurchaseRequestDetail::route('/create'),
            'edit' => Pages\EditPurchaseRequestDetail::route('/{record}/edit'),
        ];
    }
}
