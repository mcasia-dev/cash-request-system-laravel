<?php

namespace App\Filament\Resources;

use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use App\Filament\Resources\ForPaymentProcessingRevolvingFundResource\Pages;
use App\Models\RevolvingFund\ForPaymentProcessingRevolvingFund;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ForPaymentProcessingRevolvingFundResource extends Resource
{
    protected static ?string $model = ForPaymentProcessingRevolvingFund::class;
    protected static ?string $navigationGroup = 'Revolving Funds';
    protected static ?string $navigationLabel = 'For Payment Processing';
    protected static ?string $label = 'For Payment Processing';
    protected static ?string $slug = 'for-payment-processing-revolving-funds';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('status', Status::IN_PROGRESS->value)
            ->where('status_remarks', StatusRemarks::FOR_PAYMENT_PROCESSING->value)
            ->count();

        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', Status::IN_PROGRESS->value)
            ->where('status_remarks', StatusRemarks::FOR_PAYMENT_PROCESSING->value);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
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

                Tables\Columns\TextColumn::make('modeOfTransfer.name')
                    ->label('Mode of Transfer')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        Status::PENDING->value => 'warning',
                        Status::IN_PROGRESS->value => 'secondary',
                        Status::APPROVED->value => 'success',
                        Status::REJECTED->value => 'danger',
                        Status::RELEASED->value => 'info',
                        Status::REPLENISHED->value => 'gray',
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
            'index' => Pages\ListForPaymentProcessingRevolvingFunds::route('/'),
            'create' => Pages\CreateForPaymentProcessingRevolvingFund::route('/create'),
            'edit' => Pages\EditForPaymentProcessingRevolvingFund::route('/{record}/edit'),
            'view' => Pages\ViewForPaymentProcessingRevolvingFund::route('/{record}/view'),
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
