<?php
namespace App\Filament\Resources;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\ForApprovalRequestResource\Pages;
use App\Models\CashRequest;
use App\Services\CashRequestApprovalFlowService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ForApprovalRequestResource extends Resource
{
    protected static ?string $model           = CashRequest::class;
    protected static ?string $navigationGroup = 'Cash Requests';
    protected static ?string $slug            = 'for-approval-requests';
    protected static ?string $navigationLabel = 'For Approval Requests';
    protected static ?string $label           = 'For Approval Requests';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $query = app(CashRequestApprovalFlowService::class)->filterPendingForUser(static::getModel()::query(), $user);
        $count = $query->count();

        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return app(CashRequestApprovalFlowService::class)->filterPendingForUser(parent::getEloquentQuery(), $user);
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
                    ->url(fn($record) => route('filament.admin.resources.for-approval-requests.view', $record)),

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
            'index'  => Pages\ListForApprovalRequests::route('/'),
            'create' => Pages\CreateForApprovalRequest::route('/create'),
            'edit'   => Pages\EditForApprovalRequest::route('/{record}/edit'),
            'view'   => Pages\ViewForApprovalRequest::route('/{record}/view'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
