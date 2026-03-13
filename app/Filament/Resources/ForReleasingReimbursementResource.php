<?php

namespace App\Filament\Resources;

use App\Enums\CashRequest\Status;
use App\Enums\Reimbursement\StatusRemarks;
use App\Filament\Resources\ForReleasingReimbursementResource\Pages;
use App\Models\Reimbursement\ForReleasing;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ForReleasingReimbursementResource extends Resource
{
    protected static ?string $model = ForReleasing::class;
    protected static ?string $navigationGroup = 'Reimbursements';
    protected static ?string $navigationLabel = 'For Releasing';
    protected static ?string $slug = 'for-releasing-reimbursements';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('status', Status::APPROVED->value)
            ->where('status_remarks', StatusRemarks::FOR_RELEASING->value)
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
            ->where('status', Status::APPROVED->value)
            ->where('status_remarks', StatusRemarks::FOR_RELEASING->value);
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

                Tables\Columns\TextColumn::make('releasing_date')
                    ->label('Releasing Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_remarks')
                    ->label('Status Remarks')
                    ->badge()
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
            'index' => Pages\ListForReleasingReimbursements::route('/'),
            'create' => Pages\CreateForReleasingReimbursement::route('/create'),
            'edit' => Pages\EditForReleasingReimbursement::route('/{record}/edit'),
            'view' => Pages\ViewForReleasingReimbursement::route('/{record}/view'),
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

