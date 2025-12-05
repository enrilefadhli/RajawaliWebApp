<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseResource\Pages;
use App\Filament\Admin\Resources\PurchaseResource\RelationManagers\DetailsRelationManager;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationGroup = 'Purchasing';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 42;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('code')
                ->label('Purchase Code')
                ->content(fn (?Purchase $record) => $record?->code ?? 'Will be generated on save'),
            Forms\Components\Placeholder::make('user_id')
                ->label('Handled By')
                ->content(fn (?Purchase $record) => $record?->user?->name ?? Auth::user()?->name ?? '-'),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'supplier_name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('purchase_order_id')
                ->relationship('purchaseOrder', 'code')
                ->searchable()
                ->preload()
                ->label('Purchase Order (optional)')
                ->getOptionLabelFromRecordUsing(fn (PurchaseOrder $record) => $record->code ?? (string) $record->id),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
            Forms\Components\Repeater::make('items')
                ->label('Items')
                ->columnSpanFull()
                ->minItems(1)
                ->default(fn () => [['quantity' => 1]])
                ->addActionLabel('Add Item')
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label('Product')
                        ->options(fn () => Product::whereIn('status', ['ACTIVE', 'STORED'])->pluck('product_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $price = Product::find($state)?->purchase_price ?? 0;
                                $set('price', $price);
                            }
                        }),
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(1),
                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->prefix('Rp'),
                ])
                ->columns(3),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Purchase Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Handled By')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('supplier.supplier_name')->label('Supplier')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('purchaseOrder.code')->label('PO'),
                Tables\Columns\TextColumn::make('total_amount')->money('idr', true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'supplier_name'),
                Tables\Filters\TernaryFilter::make('has_po')
                    ->label('Has PO')
                    ->trueLabel('With PO')
                    ->falseLabel('Without PO')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('purchase_order_id'),
                        false: fn ($query) => $query->whereNull('purchase_order_id'),
                    ),
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'view' => Pages\ViewPurchase::route('/{record}'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
        ];
    }

    protected static function allowedRoles(): array
    {
        return ['ADMIN', 'PURCHASING'];
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && collect(self::allowedRoles())->some(fn ($role) => $user->hasRole($role));
    }

    public static function canViewAny(): bool
    {
        return self::canCreate();
    }

    public static function canEdit($record): bool
    {
        return self::canCreate();
    }

    public static function canView($record): bool
    {
        return self::canCreate();
    }

    public static function canDelete($record): bool
    {
        return self::canCreate();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canCreate();
    }
}
