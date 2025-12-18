<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Sale Info')
                ->schema([
                    Forms\Components\Hidden::make('user_id')
                        ->default(fn () => auth()->user()?->getKey()),
                    Forms\Components\TextInput::make('handled_by')
                        ->label('Handled By')
                        ->default(fn () => auth()->user()?->name)
                        ->afterStateHydrated(function ($set, $state, $record) {
                            if ($record && $record->relationLoaded('user')) {
                                $set('handled_by', $record->user?->name);
                            } elseif ($record) {
                                $set('handled_by', $record->user()->value('name'));
                            }
                        })
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\DateTimePicker::make('sale_date')
                        ->default(now())
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Items')
                ->visibleOn(['create'])
                ->schema([
                    Forms\Components\Repeater::make('details')
                        ->relationship('details')
                        ->minItems(1)
                        ->label('Products')
                        ->addActionLabel('Add items')
                        ->columns(5)
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->relationship('product', 'product_name', function ($query) {
                                    $query
                                        ->where('status', 'ACTIVE')
                                        ->whereHas('batchOfStocks', function ($batchQuery) {
                                            $batchQuery
                                                ->where('quantity', '>', 0)
                                                ->where(function ($q) {
                                                    $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
                                                });
                                        });
                                })
                                ->getOptionLabelFromRecordUsing(function (Product $record) {
                                    $variant = $record->variant ? " ({$record->variant})" : '';
                                    return trim("{$record->product_name}{$variant}") . " — Stok tersedia: {$record->available_stock}";
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if (! $state) {
                                        $set('available_quantity', 0);
                                        $set('price', 0);
                                        return;
                                    }

                                    $product = Product::query()
                                        ->withSum(['batchOfStocks as available_stock_sum' => function ($query) {
                                            $query
                                                ->where('quantity', '>', 0)
                                                ->where(function ($q) {
                                                    $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
                                                });
                                        }], 'quantity')
                                        ->find($state);

                                    $set('available_quantity', (int) ($product?->available_stock_sum ?? 0));
                                    $set('price', (float) ($product?->selling_price ?? 0));
                                }),
                            Forms\Components\Hidden::make('available_quantity')
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(fn (Get $get) => $get('available_quantity') ?: null)
                                ->reactive()
                                ->suffix('pcs'),
                            Forms\Components\TextInput::make('price')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->reactive(),
                            Forms\Components\Placeholder::make('line_total')
                                ->label('Line Total')
                                ->content(function (Get $get) {
                                    $quantity = (int) ($get('quantity') ?? 0);
                                    $price = (float) ($get('price') ?? 0);
                                    $lineTotal = $quantity * $price;
                                    return 'Rp ' . number_format($lineTotal, 0, ',', '.');
                                })
                                ->columnSpan(2),
                        ]),
                ])
                ->columnSpanFull(),
            Forms\Components\Section::make('Summary')
                ->visibleOn(['create'])
                ->schema([
                    Forms\Components\Placeholder::make('total_amount_preview')
                        ->label('Total Amount')
                        ->content(function (Get $get) {
                            $details = collect($get('details') ?? []);

                            $total = $details->sum(function ($item) {
                                return (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
                            });

                            return 'Rp ' . number_format($total, 0, ',', '.');
                        })
                        ->columnSpanFull(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sale_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Code')
                    ->formatStateUsing(fn ($state) => 'S-' . str_pad((string) $state, 5, '0', STR_PAD_LEFT))
                    ->sortable()
                    ->searchable(
                        query: function ($query, $search) {
                            $numeric = (int) preg_replace('/\D/', '', (string) $search);
                            return $query->where('id', $numeric);
                        },
                    ),
                Tables\Columns\TextColumn::make('user.name')->label('Handled By')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->money('idr', true),
                Tables\Columns\TextColumn::make('sale_date')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('sale_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('sale_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('sale_date', '<=', $date));
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
