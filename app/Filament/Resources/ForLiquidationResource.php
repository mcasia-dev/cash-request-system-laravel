<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ForCashRelease;
use App\Models\ForLiquidation;
use Filament\Resources\Resource;
use App\Enums\CashRequest\Status;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\ForLiquidationResource\Pages;

class ForLiquidationResource extends Resource
{
    protected static ?string $model           = ForLiquidation::class;
    protected static ?string $navigationGroup = 'Cash Requests';
    protected static ?string $navigationIcon  = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        $count = ForCashRelease::whereHas('cashRequest', function ($query) {
            $query->where('status', Status::RELEASED->value);
        })->count();
        
        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->whereHas('roles', function ($query) {
    //             $query->where('name', 'User');
    //         });
    // }

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
                    ->label('Requesting Amount')
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

                TextColumn::make('created_at')
                    ->label('Liquidation Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('cashRequest.date_liquidated')
                    ->label('Date Liquidated')
                    ->date()
                    ->sortable(),

                TextColumn::make('aging')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('cashRequest.status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Status::PENDING->value    => 'warning',
                        Status::APPROVED->value   => 'success',
                        Status::REJECTED->value   => 'danger',
                        Status::CANCELLED->value  => 'gray',
                        Status::LIQUIDATED->value => 'info',
                        Status::RELEASED->value   => 'info',
                        default                   => 'secondary',
                    })
                    ->searchable(),

                TextColumn::make('cashRequest.status_remarks')
                    ->label('Status Remarks')
                    ->badge()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListForLiquidations::route('/'),
            'create' => Pages\CreateForLiquidation::route('/create'),
            'edit'   => Pages\EditForLiquidation::route('/{record}/edit'),
            'view'   => Pages\ViewForLiquidation::route('/{record}/view'),
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
