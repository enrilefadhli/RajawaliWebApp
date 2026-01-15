<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('supplier_code')
                ->label('Supplier Code')
                ->required()
                ->minLength(4)
                ->maxLength(4)
                ->rule('size:4')
                ->helperText('Use exactly 4 characters (e.g., UNVR, NEST, INDO)'),
            Forms\Components\TextInput::make('supplier_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('supplier_phone')
                ->label('Phone Number')
                ->required()
                ->tel()
                ->minLength(8)
                ->maxLength(20)
                ->rule('regex:/^[0-9+\\-\\s]+$/')
                ->helperText('Include digits (and +, -, spaces if needed), 8-20 characters.'),
            Forms\Components\TextInput::make('supplier_address')
                ->label('Supplier Email')
                ->email()
                ->maxLength(255)
                ->helperText('Supplier email (optional).'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('supplier_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier_phone'),
                Tables\Columns\TextColumn::make('supplier_address')->label('Supplier Email'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessMasterData() ?? false;
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

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }
}
