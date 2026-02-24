<?php

namespace App\Filament\Resources;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\ForCashReleaseResource\Pages;
use App\Models\ForCashRelease;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ForCashReleaseResource extends Resource
{
    protected static ?string $model = ForCashRelease::class;
    protected static ?string $navigationGroup = 'For Approval';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationBadge(): ?string
    {
        $count = ForCashRelease::whereHas('cashRequest', function ($query) {
            $query->where('status', Status::APPROVED->value)
                ->where('status_remarks', StatusRemarks::FOR_RELEASING->value);
        })->count();

        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
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
                TextColumn::make('cashRequest.request_no')
                    ->label('Request No.')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('cashRequest.user.name')
                    ->label('Requestor')
                    ->searchable(),

                TextColumn::make('cashRequest.requesting_amount')
                    ->label('Total Requesting Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('cashRequest.created_at')
                    ->label('Date Requested')
                    ->date()
                    ->sortable(),

                TextColumn::make('releasing_date')
                    ->label('Releasing Date')
                    ->formatStateUsing(function ($record) {
                        return "{$record->releasing_date->format('F d, Y')} "
                            . Carbon::parse($record->releasing_time_from)->format('h:i A')
                            . ' - '
                            . Carbon::parse($record->releasing_time_to)->format('h:i A');
                    })
                    ->sortable(),

                TextColumn::make('cashRequest.due_date')
                    ->label('Liquidation Due Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('cashRequest.status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Status::PENDING->value => 'warning',
                        Status::APPROVED->value => 'success',
                        Status::REJECTED->value => 'danger',
                        Status::CANCELLED->value => 'gray',
                        Status::LIQUIDATED->value => 'info',
                        Status::RELEASED->value => 'primary',
                        default => 'secondary',
                    })
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Status::filamentOptions())
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if (!$value) {
                            return $query;
                        }

                        return $query->whereHas('cashRequest', function ($cashRequestQuery) use ($value) {
                            $cashRequestQuery->where('status', $value);
                        });
                    }),
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
            'index' => Pages\ListForCashReleases::route('/'),
            'create' => Pages\CreateForCashRelease::route('/create'),
            'edit' => Pages\EditForCashRelease::route('/{record}/edit'),
            'view' => Pages\ViewForCashRelease::route('/{record}/view'),
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
