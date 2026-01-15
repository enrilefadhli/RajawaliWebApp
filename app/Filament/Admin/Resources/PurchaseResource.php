<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseResource\Pages;
use App\Filament\Admin\Resources\PurchaseResource\RelationManagers\DetailsRelationManager;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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
                ->addActionLabel('Add items')
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label('Product')
                        ->options(function () {
                            return Product::whereIn('status', ['ACTIVE', 'STORED'])
                                ->orderBy('product_name')
                                ->get()
                                ->mapWithKeys(fn ($product) => [
                                    $product->id => trim("{$product->product_name} {$product->product_code}" . ($product->variant ? " ({$product->variant})" : '')),
                                ]);
                        })
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
                        ->prefix('Rp')
                        ->stripCharacters(',')
                        ->mask(\Filament\Support\RawJs::make('$money($input)'))
                        ->dehydrateStateUsing(fn ($state) => $state === null ? null : str_replace(',', '', (string) $state)),
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
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'COMPLETED',
                        'danger' => 'VOIDED',
                    ])
                    ->sortable(),
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
                Tables\Actions\EditAction::make()
                    ->visible(fn (Purchase $record) => $record->status !== 'VOIDED'),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('void_reason')
                            ->label('Void Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (Purchase $record) => $record->status !== 'VOIDED' && auth()->user()?->hasRole('ADMIN'))
                    ->action(function (array $data, Purchase $record) {
                        $record->voidPurchase(auth()->user()?->getKey(), $data['void_reason'] ?? null);

                        Notification::make()
                            ->title('Purchase voided')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
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

    public static function canCreate(): bool
    {
        return auth()->user()?->canAccessPurchasing() ?? false;
    }

    public static function canViewAny(): bool
    {
        return self::canCreate();
    }

    public static function canEdit($record): bool
    {
        return self::canCreate() && $record?->status !== 'VOIDED';
    }

    public static function canView($record): bool
    {
        return self::canCreate();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canCreate();
    }
}
