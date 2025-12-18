<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BatchOfStockResource\Pages;
use App\Models\BatchOfStock;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BatchOfStockResource extends Resource
{
    protected static ?string $model = BatchOfStock::class;

    protected static ?string $navigationGroup = 'Stock System';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_filter')
                ->label('Category')
                ->relationship('product.category', 'category_name')
                ->searchable()
                ->preload()
                ->dehydrated(false)
                ->reactive(),
            Forms\Components\Select::make('product_id')
                ->label('Product')
                ->options(function (callable $get) {
                    return Product::query()
                        ->when($get('category_filter'), fn ($q, $catId) => $q->where('category_id', $catId))
                        ->where('status', 'ACTIVE')
                        ->orderBy('product_name')
                        ->get()
                        ->mapWithKeys(fn ($product) => [
                            $product->id => trim($product->product_name . ($product->variant ? " ({$product->variant})" : '')),
                        ]);
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('expiry_date')
                ->required()
                ->label('Expiry Date'),
            Forms\Components\TextInput::make('quantity')->numeric()->required()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')->label('Product')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('product.product_code')->label('Product Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('batch_no')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('expiry_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('quantity')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product.category_id')
                    ->label('Category')
                    ->relationship('product.category', 'category_name'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring in 60 days')
                    ->query(fn (Builder $query) => $query->whereDate('expiry_date', '<=', now()->addDays(60))),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query) => $query->whereDate('expiry_date', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatchOfStocks::route('/'),
            'create' => Pages\CreateBatchOfStock::route('/create'),
            'edit' => Pages\EditBatchOfStock::route('/{record}/edit'),
            'view' => Pages\ViewBatchOfStock::route('/{record}'),
        ];
    }
}
