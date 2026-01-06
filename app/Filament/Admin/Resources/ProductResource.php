<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductResource\Pages;
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
            Forms\Components\TextInput::make('purchase_price')->numeric()->required(),
            Forms\Components\TextInput::make('selling_price')->numeric()->required(),
            Forms\Components\TextInput::make('discount_percent')
                ->numeric()
                ->nullable()
                ->minValue(0)
                ->maxValue(100)
                ->reactive()
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
                ->maxValue(fn (Get $get) => $get('selling_price') ?: null)
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set) {
                    $value = (float) ($state ?? 0);
                    if ($value > 0) {
                        $set('discount_percent', null);
                    }
                }),
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
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('product_code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('sku')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('product_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('variant')->searchable(),
                Tables\Columns\TextColumn::make('category.category_name')->label('Category')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('selling_price')->money('idr', true)->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')->money('idr', true)->sortable(),
                Tables\Columns\TextColumn::make('minimum_stock'),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                Action::make('export_xlsx')
                    ->label('Export XLSX')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(function ($livewire) {
                        $filename = 'products-' . now()->format('Ymd-His') . '.xlsx';

                        $query = $livewire->getFilteredTableQuery()
                            ->with(['category']);

                        $products = $query->get();

                        $filters = [
                            'category' => $livewire->tableFilters['category_id']['value'] ?? 'All',
                            'status' => $livewire->tableFilters['status']['value'] ?? 'All',
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
