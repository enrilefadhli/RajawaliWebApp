<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseRequestApprovalResource\Pages;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestApproval;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Filament\Forms;
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

    protected static ?string $navigationLabel = 'Approval';

    protected static ?int $navigationSort = 42;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canApprovePurchaseOrders() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canApprovePurchaseOrders() ?? false;
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('PR')->sortable(),
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
                Tables\Actions\ViewAction::make(),
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
                                    'status' => 'UNPROCESSED',
                                ]);

                                foreach ($record->details as $detail) {
                                    PurchaseOrderDetail::create([
                                        'purchase_order_id' => $po->id,
                                        'product_id' => $detail->product_id,
                                        'quantity' => $detail->quantity,
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
