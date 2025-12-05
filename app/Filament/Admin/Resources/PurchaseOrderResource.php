<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchaseOrderResource\Pages;
use App\Filament\Admin\Resources\PurchaseOrderResource\RelationManagers\DetailsRelationManager;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Purchasing';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?int $navigationSort = 41;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('code')
                ->label('PO Code')
                ->content(fn (?PurchaseOrder $record) => $record?->code ?? 'Will be generated on save'),
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
                ->label('Invoice / Receipt (PDF)')
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
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('PO Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('purchaseRequest.code')->label('PR Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
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
        return auth()->user()?->canApprovePurchaseOrders() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canApprovePurchaseOrders() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canApprovePurchaseOrders() ?? false;
    }
}
