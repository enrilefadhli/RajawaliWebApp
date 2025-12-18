<?php

namespace App\Filament\Admin\Resources\PurchaseResource\Pages;

use App\Filament\Admin\Resources\PurchaseResource;
use App\Models\Product;
use App\Services\PurchaseService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ListPurchases extends ListRecords
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $canDirect = $user && $user->canApprovePurchaseOrders();

        return [
            Actions\Action::make('new_purchase_request')
                ->label('New Purchase Request')
                ->icon('heroicon-o-plus')
                ->action(fn () => Redirect::to(route('filament.admin.resources.purchase-requests.create'))),
            Actions\Action::make('direct_purchase')
                ->label('Direct Purchase')
                ->visible($canDirect)
                ->modalHeading('Direct Purchase')
                ->modalButton('Create Purchase')
                ->form([
                    Forms\Components\Select::make('supplier_id')
                        ->relationship('supplier', 'supplier_name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Supplier'),
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
                            Forms\Components\DatePicker::make('expiry_date')
                                ->label('Expiry Date')
                                ->required()
                                ->native(false),
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
                                ->step(0.01),
                        ])
                        ->columns(4),
                ])
                ->action(function (array $data) {
                    app(PurchaseService::class)->createDirectPurchase($data);
                })
                ->successRedirectUrl(PurchaseResource::getUrl('index')),
        ];
    }
}
