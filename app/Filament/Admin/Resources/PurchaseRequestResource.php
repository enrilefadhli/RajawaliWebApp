<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseRequestResource\Pages;
use App\Filament\Admin\Resources\PurchaseRequestResource\RelationManagers\DetailsRelationManager;
use App\Filament\Admin\Resources\PurchaseRequestResource\RelationManagers\ApprovalsRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseRequest;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static ?string $navigationGroup = 'Purchasing';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('code')
                ->label('PR Code')
                ->content(fn (?PurchaseRequest $record) => $record?->code ?? 'Will be generated on save'),
            Forms\Components\Placeholder::make('requested_by')
                ->label('Requested By')
                ->content(fn () => Auth::user()?->name ?? '-'),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'supplier_name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Textarea::make('request_note')->columnSpanFull(),
            Forms\Components\Repeater::make('details')
                ->columnSpanFull()
                ->label('Products')
                ->relationship('details')
                ->addActionLabel('Add product')
                ->extraAttributes(['class' => 'pr-details-left'])
                ->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->options(fn () => Category::pluck('category_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('product_id', null))
                        ->required(),
                    Forms\Components\Select::make('product_id')
                        ->options(function (callable $get) {
                            $categoryId = $get('category_id');
                            $query = Product::query();
                            if ($categoryId) {
                                $query->where('category_id', $categoryId);
                            }
                            return $query->pluck('product_name', 'id');
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Product'),
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->label('Quantity'),
                ])
                ->columns(3)
                ->minItems(1)
                ->default(fn () => [['quantity' => 1]]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('PR Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('requester.name')->label('Requested By')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('approver.name')->label('Approved By')->sortable(),
                Tables\Columns\TextColumn::make('supplier.supplier_name')->label('Supplier')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('requested_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('handled_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->money('idr', true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'PENDING',
                        'APPROVED' => 'APPROVED',
                        'REJECTED' => 'REJECTED',
                    ]),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'supplier_name')
                    ->label('Supplier'),
                Tables\Filters\Filter::make('requested_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('requested_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('requested_at', '<=', $date));
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
            'index' => Pages\ListPurchaseRequests::route('/'),
            'create' => Pages\CreatePurchaseRequest::route('/create'),
            'view' => Pages\ViewPurchaseRequest::route('/{record}'),
            'edit' => Pages\EditPurchaseRequest::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
            ApprovalsRelationManager::class,
        ];
    }
}
