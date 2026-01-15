<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseOrderResource\Pages;
use App\Filament\Admin\Resources\PurchaseOrderResource\RelationManagers\DetailsRelationManager;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Services\PurchaseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Purchasing';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?int $navigationSort = 42;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'details as missing_expiry_count' => fn (Builder $query) => $query->whereNull('expiry_date'),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('code')
                ->label('PO Code')
                ->content(fn (?PurchaseOrder $record) => $record?->code ?? 'Will be generated on save'),
            Forms\Components\Placeholder::make('expiry_notice')
                ->label('Expiry Date Check')
                ->content(function (?PurchaseOrder $record) {
                    if (! $record) {
                        return 'Expiry dates will be validated before completion.';
                    }

                    $missing = $record->details()->whereNull('expiry_date')->count();
                    if ($missing <= 0) {
                        return 'All items have expiry dates.';
                    }

                    return "Missing expiry date for {$missing} item(s).";
                })
                ->visible(fn (?PurchaseOrder $record) => (bool) $record)
                ->columnSpanFull(),
            Forms\Components\Select::make('purchase_request_id')
                ->relationship('purchaseRequest', 'id')
                ->required(fn (string $operation): bool => $operation === 'create')
                ->label('Purchase Request')
                ->searchable(['code'])
                ->getOptionLabelFromRecordUsing(fn (PurchaseRequest $record) => $record->code ?? (string) $record->id)
                ->disabledOn('edit')
                ->hiddenOn('edit')
                ->dehydrated(fn (string $operation): bool => $operation === 'create'),
            Forms\Components\Hidden::make('status')
                ->default('ONPROGRESS'),
            FileUpload::make('attachment_path')
                ->label('Invoice / Receipt / Signed Purchase Order (PDF)')
                ->acceptedFileTypes(['application/pdf'])
                ->directory('purchase-orders')
                ->disk('public')
                ->visibility('public')
                ->downloadable()
                ->openable()
                ->preserveFilenames()
                ->required(fn (string $operation): bool => $operation === 'edit'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordAction('edit')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('PO Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('purchaseRequest.code')->label('PR Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'ONPROGRESS',
                        'success' => 'COMPLETED',
                        'danger' => 'CANCELLED',
                    ]),
                Tables\Columns\TextColumn::make('purchaseRequest.total_expected_amount')
                    ->label('Total Expected')
                    ->money('idr', true),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Actual')
                    ->money('idr', true),
                Tables\Columns\TextColumn::make('missing_expiry_count')
                    ->label('Missing Expiry')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('attachment_path')
                    ->label('Attachment')
                    ->url(fn ($record) => $record->attachment_path ? Storage::disk('public')->url($record->attachment_path) : null, true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ONPROGRESS' => 'ONPROGRESS',
                        'COMPLETED' => 'COMPLETED',
                        'CANCELLED' => 'CANCELLED',
                    ]),
            ])
            ->actions([
                Action::make('download_po')
                    ->label('Download PO')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(fn (PurchaseOrder $record) => self::downloadPurchaseOrderPdf($record)),
                Action::make('cancel_po')
                    ->label('Cancel PO')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseOrder $record) => $record->status !== 'COMPLETED' && $record->status !== 'CANCELLED')
                    ->form([
                        Forms\Components\Textarea::make('cancel_reason')
                            ->label('Cancel reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, PurchaseOrder $record) {
                        $record->update([
                            'status' => 'CANCELLED',
                            'cancel_reason' => $data['cancel_reason'] ?? null,
                            'cancelled_by' => auth()->user()?->getKey(),
                            'cancelled_at' => now(),
                        ]);

                        Notification::make()
                            ->title('PO cancelled')
                            ->success()
                    ->send();
            }),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record?->status !== 'COMPLETED' && $record?->status !== 'CANCELLED'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
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

    public static function canEdit($record): bool
    {
        if ($record?->status === 'COMPLETED' || $record?->status === 'CANCELLED') {
            return false;
        }
        return auth()->user()?->canAccessPurchasing() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canAccessPurchasing() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessPurchasing() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function downloadPurchaseOrderPdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->loadMissing([
            'purchaseRequest.supplier',
            'purchaseRequest.requester',
            'details.product',
        ]);

        $supplier = $purchaseOrder->purchaseRequest?->supplier;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
            'supplier' => $supplier,
        ])->setPaper('a4');

        $safeCode = preg_replace('/[^A-Za-z0-9\\-_]/', '-', (string) ($purchaseOrder->code ?? 'po'));
        $filename = "{$safeCode}.pdf";

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
