<?php

namespace App\Filament\Widgets;

use App\Enums\CashRequest\Status;
use App\Models\CashRequest\ForLiquidation;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UnliquidatedCashRequestsTable extends BaseWidget
{
    protected static ?string $heading = 'Unliquidated Cash Requests';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    public static function canView(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->hasRole('treasury_staff')
            || $user->hasRole('treasury_manager')
            || $user->hasRole('department_head');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('cashRequest.request_no')
                    ->label('Request No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cashRequest.user.name')
                    ->label('Requestor')
                    ->searchable(),

                TextColumn::make('cashRequest.created_at')
                    ->label('Date Requested')
                    ->date()
                    ->sortable(),

                TextColumn::make('cashRequest.requesting_amount')
                    ->label('Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('cashRequest.date_released')
                    ->label('Date Released')
                    ->date()
                    ->sortable(),

                TextColumn::make('cashRequest.due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('days_due')
                    ->label('Aging')
                    ->state(function (ForLiquidation $record): string {
                        $dueDate = $record->cashRequest?->due_date;

                        if (!$dueDate) {
                            return '-';
                        }

                        $days = now()->startOfDay()->diffInDays($dueDate->startOfDay(), false);

                        return $days < 0
                            ? abs($days) . ' day(s) overdue'
                            : $days . ' day(s) left';
                    })
                    ->badge()
                    ->color(function (ForLiquidation $record): string {
                        $dueDate = $record->cashRequest?->due_date;

                        if (!$dueDate) {
                            return 'gray';
                        }

                        return now()->startOfDay()->greaterThan($dueDate->startOfDay())
                            ? 'danger'
                            : 'success';
                    }),

                TextColumn::make('cashRequest.status_remarks')
                    ->label('Status Remarks')
                    ->badge()
                    ->color('info')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn(ForLiquidation $record): string => route('filament.admin.resources.for-liquidations.view', ['record' => $record->id])),
            ])
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5])
            ->emptyStateHeading('No unliquidated cash requests');
    }

    protected function getTableQuery(): Builder
    {
        return ForLiquidation::query()
            ->with(['cashRequest.user'])
            ->whereHas('cashRequest', function (Builder $query): void {
                $query->where('status', Status::RELEASED->value);
            });
    }
}
