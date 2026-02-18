<?php
namespace App\Filament\Resources;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\PaymentProcessResource\Pages;
use App\Models\CashRequest;
use App\Models\PaymentProcess;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentProcessResource extends Resource
{
    protected static ?string $model           = PaymentProcess::class;
    protected static ?string $navigationGroup = 'For Approval';
    protected static ?string $slug            = 'payment-processing';
    protected static ?string $navigationLabel = 'Payment Processsing';
    protected static ?string $label           = 'Payment Processsing';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', Status::IN_PROGRESS->value)
            ->where('status_remarks', StatusRemarks::FOR_PAYMENT_PROCESSING->value)
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
        // ->whereHas('roles', function ($query) {
        //     $query->where('name', 'User');
        // })
            ->where('status', Status::IN_PROGRESS->value)
            ->where('status_remarks', StatusRemarks::FOR_PAYMENT_PROCESSING->value);
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
                TextColumn::make('request_no')
                    ->label('Request No.')
                    ->sortable()
                    ->searchable()
                    ->url(fn($record) => route('filament.admin.resources.payment-processing.view', $record)),

                TextColumn::make('user.name')
                    ->label('Requestor')
                    ->searchable(),

                TextColumn::make('requesting_amount')
                    ->label('Total Requesting Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Status::PENDING->value    => 'warning',
                        Status::APPROVED->value   => 'success',
                        Status::REJECTED->value   => 'danger',
                        Status::CANCELLED->value  => 'gray',
                        Status::LIQUIDATED->value => 'info',
                        Status::RELEASED->value   => 'primary',
                        default                   => 'secondary',
                    })
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
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
            'index'  => Pages\ListPaymentProcesses::route('/'),
            'create' => Pages\CreatePaymentProcess::route('/create'),
            'edit'   => Pages\EditPaymentProcess::route('/{record}/edit'),
            'view'   => Pages\ViewPaymentProcess::route('/{record}/view'),
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
