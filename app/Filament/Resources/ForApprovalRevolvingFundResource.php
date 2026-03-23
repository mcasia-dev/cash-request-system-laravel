<?php

namespace App\Filament\Resources;

use App\Enums\RevolvingFund\Status;
use App\Filament\Resources\ForApprovalRevolvingFundResource\Pages;
use App\Models\RevolvingFund\ForApprovalRevolvingFund;
use App\Services\RevolvingFund\RevolvingFundApprovalFlowService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ForApprovalRevolvingFundResource extends Resource
{
    protected static ?string $model = ForApprovalRevolvingFund::class;
    protected static ?string $navigationGroup = 'Revolving Funds';
    protected static ?string $navigationLabel = 'For Approval';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $count = app(RevolvingFundApprovalFlowService::class)
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

        return app(RevolvingFundApprovalFlowService::class)
            ->filterPendingForUser(parent::getEloquentQuery(), $user);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fund_code')
                    ->label('Fund Code')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('addedBy.name')
                    ->label('Requestor')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Recipient')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('initial_amount')
                    ->label('Initial Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Remaining Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        Status::PENDING->value => 'warning',
                        Status::IN_PROGRESS->value => 'secondary',
                        Status::APPROVED->value => 'success',
                        Status::REJECTED->value => 'danger',
                        Status::REPLENISHED->value => 'info',
                        Status::RELEASED->value => 'info',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('status_remarks')
                    ->label('Status Remarks')
                    ->badge()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForApprovalRevolvingFunds::route('/'),
            'create' => Pages\CreateForApprovalRevolvingFund::route('/create'),
            'edit' => Pages\EditForApprovalRevolvingFund::route('/{record}/edit'),
            'view' => Pages\ViewForApprovalRevolvingFund::route('/{record}/view'),
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
