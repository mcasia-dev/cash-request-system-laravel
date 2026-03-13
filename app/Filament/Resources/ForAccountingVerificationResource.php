<?php

namespace App\Filament\Resources;

use App\Enums\CashRequest\Status;
use App\Enums\Reimbursement\StatusRemarks;
use App\Filament\Resources\ForAccountingVerificationResource\Pages;
use App\Models\Reimbursement\ForAccountingVerification;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ForAccountingVerificationResource extends Resource
{
    protected static ?string $model = ForAccountingVerification::class;
    protected static ?string $navigationGroup = 'Reimbursements';
    protected static ?string $navigationLabel = 'For Accounting Verification';
    protected static ?string $slug = 'for-accounting-verifications';
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('status', Status::IN_PROGRESS->value)
            ->whereIn('status_remarks', [
                StatusRemarks::FOR_ACCOUNTING_VERIFICATION->value,
                StatusRemarks::ACCOUNTING_OVERRIDE_COMPLETED->value,
                StatusRemarks::ACCOUNTING_MANAGER_APPROVED->value,
            ])
            ->count();

        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', Status::IN_PROGRESS->value)
            ->whereIn('status_remarks', [
                StatusRemarks::FOR_ACCOUNTING_VERIFICATION->value,
                StatusRemarks::ACCOUNTING_OVERRIDE_COMPLETED->value,
                StatusRemarks::ACCOUNTING_MANAGER_APPROVED->value,
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reimbursement_no')
                    ->label('Reimbursement No.')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('payee.name')
                    ->label('Requestor')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('reimbursementMode.name')
                    ->label('Mode of Request')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_remarks')
                    ->label('Accounting Step')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForAccountingVerifications::route('/'),
            'create' => Pages\CreateForAccountingVerification::route('/create'),
            'edit' => Pages\EditForAccountingVerification::route('/{record}/edit'),
            'view' => Pages\ViewForAccountingVerification::route('/{record}/view'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $model): bool
    {
        return false;
    }

    public static function canDelete(Model $model): bool
    {
        return false;
    }
}

