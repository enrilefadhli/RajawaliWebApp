<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseRequestApprovalResource\Pages;
use App\Filament\Admin\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestApproval;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequestApprovalResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static ?string $navigationGroup = 'Purchasing';

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'PR Approvals';

    protected static ?int $navigationSort = 41;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canApprovePurchaseRequests() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canApprovePurchaseRequests() ?? false;
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Request Info')
                ->schema([
                    Forms\Components\Placeholder::make('code')
                        ->label('PR Code')
                        ->content(fn (?PurchaseRequest $record) => $record?->code ?? '-'),
                    Forms\Components\Placeholder::make('status')
                        ->label('Status')
                        ->content(function (?PurchaseRequest $record) {
                            $status = $record?->status ?? '-';
                            $color = match ($status) {
                                'APPROVED' => '#16a34a',
                                'REJECTED' => '#dc2626',
                                default => '#ca8a04',
                            };

                            return new HtmlString("<span style=\"color: {$color}; font-weight: 600;\">{$status}</span>");
                        }),
                    Forms\Components\Placeholder::make('requested_by')
                        ->label('Requested By')
                        ->content(fn (?PurchaseRequest $record) => $record?->requester?->name ?? '-'),
                    Forms\Components\Select::make('supplier_id')
                        ->relationship('supplier', 'supplier_name')
                        ->label('Supplier')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Textarea::make('request_note')
                        ->label('Request Note')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Items')
                ->schema([
                    Forms\Components\Repeater::make('details')
                        ->relationship('details')
                        ->disableItemCreation()
                        ->disableItemDeletion()
                        ->disableItemMovement()
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->relationship('product', 'product_name', fn ($query) => $query->where('status', 'ACTIVE'))
                                ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->product_name} {$record->product_code}" . ($record->variant ? " ({$record->variant})" : '')))
                                ->label('Product')
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('expected_unit_price')
                                ->label('Expected Unit Price')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false)
                                ->stripCharacters(',')
                                ->mask(\Filament\Support\RawJs::make('$money($input)')),
                        ])
                        ->columns(3),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Request Info')
                ->schema([
                    TextEntry::make('code')
                        ->label('PR Code'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (?string $state) => match ($state) {
                            'APPROVED' => 'success',
                            'REJECTED' => 'danger',
                            default => 'warning',
                        }),
                    TextEntry::make('requester.name')
                        ->label('Requested By'),
                    TextEntry::make('supplier.supplier_name')
                        ->label('Supplier'),
                    TextEntry::make('request_note')
                        ->label('Request Note')
                        ->columnSpanFull()
                        ->placeholder('-'),
                ])
                ->columns(2),
            Section::make('Items')
                ->schema([
                    RepeatableEntry::make('details')
                        ->label('Details')
                        ->schema([
                            TextEntry::make('product.product_name')
                                ->label('Product'),
                            TextEntry::make('product.product_code')
                                ->label('Code'),
                            TextEntry::make('quantity')
                                ->label('Quantity'),
                            TextEntry::make('expected_unit_price')
                                ->label('Expected Unit Price')
                                ->money('idr', true),
                        ])
                        ->columns(4),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('PR Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('requester.name')->label('Requested By')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('supplier.supplier_name')->label('Supplier')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('status')->label('Status')
                    ->colors([
                        'warning' => 'PENDING',
                        'success' => 'APPROVED',
                        'danger' => 'REJECTED',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('requested_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'PENDING' => 'PENDING',
                    'APPROVED' => 'APPROVED',
                    'REJECTED' => 'REJECTED',
                ])->default('PENDING'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_pr')
                    ->label('View PR')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (PurchaseRequest $record) => PurchaseRequestResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('note')->label('Note')->columnSpanFull(),
                    ])
                    ->visible(fn (PurchaseRequest $record) => $record->status === 'PENDING')
                    ->action(function (array $data, PurchaseRequest $record) {
                        DB::transaction(function () use ($data, $record) {
                            $approverId = Auth::user()?->getKey();

                            PurchaseRequestApproval::create([
                                'purchase_request_id' => $record->id,
                                'approved_by' => $approverId,
                                'status' => 'APPROVED',
                                'note' => $data['note'] ?? null,
                                'approved_at' => now(),
                            ]);

                            $record->update(['status' => 'APPROVED', 'handled_at' => now(), 'approved_by_id' => $approverId]);

                            if (! $record->purchaseOrder()->exists()) {
                                $po = PurchaseOrder::create([
                                    'purchase_request_id' => $record->id,
                                    'status' => 'ONPROGRESS',
                                ]);

                                foreach ($record->details as $detail) {
                                    PurchaseOrderDetail::create([
                                        'purchase_order_id' => $po->id,
                                        'product_id' => $detail->product_id,
                                        'quantity' => $detail->quantity,
                                        'expiry_date' => null,
                                        'expected_unit_price' => $detail->expected_unit_price ?? 0,
                                        'unit_price' => $detail->expected_unit_price ?? 0,
                                    ]);
                                }
                            }
                        });
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('note')->label('Note')->columnSpanFull(),
                    ])
                    ->visible(fn (PurchaseRequest $record) => $record->status === 'PENDING')
                    ->action(function (array $data, PurchaseRequest $record) {
                        $approverId = Auth::user()?->getKey();
                        PurchaseRequestApproval::create([
                            'purchase_request_id' => $record->id,
                            'approved_by' => $approverId,
                            'status' => 'REJECTED',
                            'note' => $data['note'] ?? null,
                            'approved_at' => now(),
                        ]);
                        $record->update(['status' => 'REJECTED', 'handled_at' => now(), 'approved_by_id' => $approverId]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequestApprovals::route('/'),
            'view' => Pages\ViewPurchaseRequestApproval::route('/{record}'),
        ];
    }
}
