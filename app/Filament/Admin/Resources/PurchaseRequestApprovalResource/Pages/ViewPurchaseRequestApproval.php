<?php

namespace App\Filament\Admin\Resources\PurchaseRequestApprovalResource\Pages;

use App\Filament\Admin\Resources\PurchaseRequestApprovalResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestApproval;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewPurchaseRequestApproval extends ViewRecord
{
    protected static string $resource = PurchaseRequestApprovalResource::class;

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('note')
                        ->label('Note')
                        ->columnSpanFull(),
                ])
                ->visible(fn () => $this->record?->status === 'PENDING')
                ->action(function (array $data) {
                    /** @var PurchaseRequest $record */
                    $record = $this->record;

                    DB::transaction(function () use ($data, $record) {
                        $approverId = Auth::user()?->getKey();

                        PurchaseRequestApproval::create([
                            'purchase_request_id' => $record->id,
                            'approved_by' => $approverId,
                            'status' => 'APPROVED',
                            'note' => $data['note'] ?? null,
                            'approved_at' => now(),
                        ]);

                        $record->update([
                            'status' => 'APPROVED',
                            'handled_at' => now(),
                            'approved_by_id' => $approverId,
                        ]);

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
            Actions\Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('note')
                        ->label('Note')
                        ->columnSpanFull(),
                ])
                ->visible(fn () => $this->record?->status === 'PENDING')
                ->action(function (array $data) {
                    /** @var PurchaseRequest $record */
                    $record = $this->record;

                    $approverId = Auth::user()?->getKey();

                    PurchaseRequestApproval::create([
                        'purchase_request_id' => $record->id,
                        'approved_by' => $approverId,
                        'status' => 'REJECTED',
                        'note' => $data['note'] ?? null,
                        'approved_at' => now(),
                    ]);

                    $record->update([
                        'status' => 'REJECTED',
                        'handled_at' => now(),
                        'approved_by_id' => $approverId,
                    ]);
                }),
        ];
    }
}
