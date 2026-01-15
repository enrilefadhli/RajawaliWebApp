<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductResource\Pages;
use App\Filament\Admin\Resources\BatchOfStockResource;
use App\Exports\ProductExport;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 22;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withSum('batchOfStocks as total_stock_sum', 'quantity')
            ->withSum(['batchOfStocks as available_stock_sum' => function ($query) {
                $query
                    ->where('quantity', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
                    });
            }], 'quantity');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Info')
                ->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'category_name')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if ($state) {
                                $set('product_code', self::generateProductCodeByCategory($state));
                            }
                        }),
                    Forms\Components\TextInput::make('product_code')
                        ->required()
                        ->maxLength(255)
                        ->default(fn (Get $get) => self::generateProductCodeByCategory($get('category_id')))
                        ->disabledOn('create')
                        ->hint('Auto-generated from category code (e.g., BEV00001)'),
                    Forms\Components\TextInput::make('sku')->maxLength(255),
                    Forms\Components\TextInput::make('product_name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('variant')->maxLength(255),
                ])
                ->columns(2),
            Forms\Components\Section::make('Pricing')
                ->schema([
                    Forms\Components\TextInput::make('purchase_price')
                        ->numeric()
                        ->required()
                        ->stripCharacters(',')
                        ->mask(\Filament\Support\RawJs::make('$money($input)'))
                        ->dehydrateStateUsing(fn ($state) => $state === null ? null : str_replace(',', '', (string) $state)),
                    Forms\Components\TextInput::make('selling_price')
                        ->numeric()
                        ->required()
                        ->stripCharacters(',')
                        ->mask(\Filament\Support\RawJs::make('$money($input)'))
                        ->dehydrateStateUsing(fn ($state) => $state === null ? null : str_replace(',', '', (string) $state))
                        ->reactive()
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            $selling = (float) str_replace(',', '', (string) ($state ?? 0));
                            $purchase = (float) str_replace(',', '', (string) ($get('purchase_price') ?? 0));
                            $warned = (bool) ($get('pricing_warning_sent') ?? false);

                            if ($selling > 0 && $selling < $purchase && ! $warned) {
                                Notification::make()
                                    ->title('Selling price is below purchase price')
                                    ->body('Please confirm the pricing; selling price is below purchase price.')
                                    ->warning()
                                    ->send();
                                $set('pricing_warning_sent', true);
                            }

                            if ($selling >= $purchase && $warned) {
                                $set('pricing_warning_sent', false);
                            }
                        }),
                    Forms\Components\Hidden::make('pricing_warning_sent')
                        ->default(false)
                        ->dehydrated(false),
                ])
                ->columns(2),
            Forms\Components\Section::make('Stock & Status')
                ->schema([
                    Forms\Components\TextInput::make('minimum_stock')->numeric()->required(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'ACTIVE' => 'Active',
                            'STORED' => 'Stored',
                            'DISABLED' => 'Disabled',
                        ])
                        ->default('DISABLED')
                        ->required(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Discount')
                ->schema([
                    Forms\Components\TextInput::make('discount_percent')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->maxValue(100)
                        ->reactive()
                        ->disabled(fn (Get $get) => (float) str_replace(',', '', (string) ($get('discount_amount') ?? 0)) > 0)
                        ->afterStateUpdated(function ($state, Set $set) {
                            $value = (float) ($state ?? 0);
                            if ($value > 0) {
                                $set('discount_amount', null);
                            }
                        }),
                    Forms\Components\TextInput::make('discount_amount')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->maxValue(fn (Get $get) => $get('selling_price') ? (float) str_replace(',', '', (string) $get('selling_price')) : null)
                        ->reactive()
                        ->disabled(fn (Get $get) => (float) ($get('discount_percent') ?? 0) > 0)
                        ->stripCharacters(',')
                        ->mask(\Filament\Support\RawJs::make('$money($input)'))
                        ->dehydrateStateUsing(fn ($state) => $state === null ? null : str_replace(',', '', (string) $state))
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $value = (float) ($state ?? 0);
                            $max = (float) str_replace(',', '', (string) ($get('selling_price') ?? 0));
                            if ($max > 0 && $value > $max) {
                                $set('discount_amount', $max);
                                $value = $max;
                            }
                            if ($value > 0) {
                                $set('discount_percent', null);
                            }
                        }),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('product_code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('product_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('variant')->searchable(),
                Tables\Columns\TextColumn::make('category.category_name')->label('Category')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('purchase_price')->money('idr', true)->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('selling_price')->money('idr', true)->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('computed_discount_amount')
                    ->label('Discount Amount')
                    ->money('idr', true)
                    ->state(fn (Product $record) => $record->computedDiscountAmount())
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('net_selling_price')
                    ->label('Net Selling Price')
                    ->money('idr', true)
                    ->state(fn (Product $record) => $record->effectiveSellingPrice())
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('minimum_stock')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('needs_restock')
                    ->label('Restock?')
                    ->getStateUsing(fn (Product $record) => (($record->available_stock_sum ?? $record->available_stock ?? 0) < ($record->minimum_stock ?? 0)) ? 'YES' : 'NO')
                    ->colors([
                        'danger' => 'YES',
                        'success' => 'NO',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'ACTIVE',
                        'warning' => 'STORED',
                        'danger' => 'DISABLED',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_stock_sum')
                    ->label('Total Stock')
                    ->default(0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('toggle_view')
                    ->label(function ($livewire) {
                        $state = $livewire->toggledTableColumns ?? [];
                        if ($state === []) {
                            return 'Stock View';
                        }

                        $detailed = [
                            'sku',
                            'purchase_price',
                            'selling_price',
                            'computed_discount_amount',
                            'net_selling_price',
                            'minimum_stock',
                            'created_at',
                        ];

                        $isDetailed = true;
                        foreach ($detailed as $column) {
                            if (data_get($state, $column) !== true) {
                                $isDetailed = false;
                                break;
                            }
                        }

                        return $isDetailed ? 'Stock View' : 'Detailed View';
                    })
                    ->icon('heroicon-o-rectangle-stack')
                    ->action(function ($livewire) {
                        $state = $livewire->toggledTableColumns ?? [];
                        $detailed = [
                            'sku',
                            'purchase_price',
                            'selling_price',
                            'computed_discount_amount',
                            'net_selling_price',
                            'minimum_stock',
                            'created_at',
                        ];

                        $isDetailed = true;
                        foreach ($detailed as $column) {
                            if (data_get($state, $column) !== true) {
                                $isDetailed = false;
                                break;
                            }
                        }

                        $livewire->toggledTableColumns = [
                            'sku' => ! $isDetailed,
                            'purchase_price' => ! $isDetailed,
                            'selling_price' => ! $isDetailed,
                            'computed_discount_amount' => ! $isDetailed,
                            'net_selling_price' => ! $isDetailed,
                            'minimum_stock' => ! $isDetailed,
                            'created_at' => ! $isDetailed,
                        ];
                        $livewire->updatedToggledTableColumns();
                    }),
                Action::make('export_xlsx')
                    ->label('Export XLSX')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(function ($livewire) {
                        $filename = 'products-' . now()->format('Ymd-His') . '.xlsx';

                        $query = $livewire->getFilteredTableQuery()
                            ->with(['category']);

                        $products = $query->get();

                        $categoryValue = $livewire->tableFilters['category_id']['value'] ?? null;
                        $statusValue = $livewire->tableFilters['status']['value'] ?? null;

                        $filters = [
                            'category' => $categoryValue ? (Category::find($categoryValue)?->category_name ?? $categoryValue) : 'All',
                            'status' => $statusValue ?: 'All',
                        ];

                        $view = view('exports.products', [
                            'products' => $products,
                            'filters' => $filters,
                        ]);

                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new ProductExport($view),
                            $filename
                        );
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'category_name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'ACTIVE' => 'Active',
                        'STORED' => 'Stored',
                        'DISABLED' => 'Disabled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('add_batch')
                    ->label('Add Batch')
                    ->icon('heroicon-o-plus')
                    ->url(fn (Product $record) => BatchOfStockResource::getUrl('create', ['product_id' => $record->id]))
                    ->visible(fn () => auth()->user()?->canAccessStockSystem() ?? false),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
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

    public static function generateProductCodeByCategory(?int $categoryId): ?string
    {
        if (! $categoryId) {
            return null;
        }

        $category = Category::find($categoryId);
        if (! $category || ! $category->category_code) {
            return null;
        }

        $prefix = strtoupper($category->category_code);

        $latest = Product::where('product_code', 'like', "{$prefix}%")
            ->orderByDesc('product_code')
            ->value('product_code');

        $next = 1;
        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%05d', $prefix, $next);
    }
}
