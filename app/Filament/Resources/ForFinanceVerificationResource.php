<?php
namespace App\Filament\Resources;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\ForFinanceVerificationResource\Pages;
use App\Models\CashRequest;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ForFinanceVerificationResource extends Resource
{
    protected static ?string $model           = CashRequest::class;
    protected static ?string $navigationGroup = 'Cash Requests';
    protected static ?string $slug            = 'for-verification';
    protected static ?string $navigationLabel = 'For Verification';
    protected static ?string $label           = 'For Verification';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', Status::IN_PROGRESS->value)
            ->where('status_remarks', StatusRemarks::FOR_FINANCE_VERIFICATION->value)
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
            ->where('status_remarks', StatusRemarks::FOR_FINANCE_VERIFICATION->value);
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
                    ->url(fn($record) => route('filament.admin.resources.for-verification.view', $record)),

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
            'index'  => Pages\ListForFinanceVerifications::route('/'),
            'create' => Pages\CreateForFinanceVerification::route('/create'),
            'edit'   => Pages\EditForFinanceVerification::route('/{record}/edit'),
            'view'   => Pages\ViewForFinanceVerification::route('/{record}/view'),
        ];
    }
}
