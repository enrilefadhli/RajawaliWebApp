<?php

namespace App\Filament\Admin\Resources\PurchaseRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    public static function canViewForRecord($ownerRecord, $page): bool
    {
        return auth()->user()?->canApprovePurchaseRequests() ?? false;
    }

    public static function canViewAnyForRecord($ownerRecord, $page): bool
    {
        return auth()->user()?->canApprovePurchaseRequests() ?? false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->options([
                    'APPROVED' => 'APPROVED',
                    'REJECTED' => 'REJECTED',
                ])
                ->required(),
            Forms\Components\Textarea::make('note')
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('approved_at')
                ->default(now()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('approver.name')->label('Approved By')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('note')->limit(40),
                Tables\Columns\TextColumn::make('approved_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['approved_by'] = Auth::user()?->getKey();
                        $data['approved_at'] = $data['approved_at'] ?? now();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
