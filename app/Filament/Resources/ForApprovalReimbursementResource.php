<?php

namespace App\Filament\Resources;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\ForApprovalReimbursementResource\Pages;
use App\Models\Reimbursement\ForApprovalReimbursement;
use App\Services\Reimbursement\ReimbursementApprovalFlowService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ForApprovalReimbursementResource extends Resource
{
    protected static ?string $model = ForApprovalReimbursement::class;
    protected static ?string $navigationGroup = 'Reimbursements';
    protected static ?string $navigationLabel = 'For Approvals';
    protected static ?string $label = 'For Approvals';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $count = app(ReimbursementApprovalFlowService::class)
            ->filterPendingForUser(static::getModel()::query(), $user)
            ->count();
        
        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (!$user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return app(ReimbursementApprovalFlowService::class)
            ->filterPendingForUser(parent::getEloquentQuery(), $user);
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

                Tables\Columns\TextColumn::make('reimbursement_date')
                    ->label('Reimbursement Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reimbursementMode.name')
                    ->label('Mode of Request')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mode_of_transfer')
                    ->label('Mode of Transfer')
                    ->sortable()
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        Status::PENDING->value => 'warning',
                        Status::IN_PROGRESS->value => 'secondary',
                        Status::REJECTED->value => 'danger',
                        Status::APPROVED->value => 'success',
                        Status::RELEASED->value => 'info',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('status_remarks')
                    ->label('Status Remarks')
                    ->sortable()
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListForApprovalReimbursements::route('/'),
            'create' => Pages\CreateForApprovalReimbursement::route('/create'),
            'edit' => Pages\EditForApprovalReimbursement::route('/{record}/edit'),
            'view' => Pages\ViewForApprovalReimbursement::route('/{record}/view'),
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
