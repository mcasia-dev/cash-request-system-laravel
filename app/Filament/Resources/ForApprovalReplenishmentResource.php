<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ForApprovalReplenishmentResource\Pages;
use App\Models\RevolvingFund\ForApprovalReplenishment;
use App\Services\RevolvingFund\ForApprovalReplenishmentService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ForApprovalReplenishmentResource extends Resource
{
    protected static ?string $model = ForApprovalReplenishment::class;
    protected static ?string $navigationGroup = 'Replenishments';
    protected static ?string $navigationLabel = 'Replenishment Approvals';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $count = app(ForApprovalReplenishmentService::class)
            ->filterPendingForUser(static::getModel()::query(), $user)
            ->count();

        return $count > 0 ? (string)$count : null;
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

        return app(ForApprovalReplenishmentService::class)
            ->filterVisibleForUser(parent::getEloquentQuery()->with(['revolvingFund.user']), $user);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('revolvingFund.fund_code')
                    ->label('Fund Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('revolvingFund.user.name')
                    ->label('Requestor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Requested Total')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'returned' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'replenished' => 'primary',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->dateTime('F d, Y H:i A')
                    ->sortable(),
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
            'index' => Pages\ListForApprovalReplenishments::route('/'),
            'view' => Pages\ViewForApprovalReplenishment::route('/{record}/view'),
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
