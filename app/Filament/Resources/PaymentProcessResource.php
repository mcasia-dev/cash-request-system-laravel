<?php
namespace App\Filament\Resources;

use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\PaymentProcessResource\Pages;
use App\Models\CashRequest;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentProcessResource extends Resource
{
    protected static ?string $model           = CashRequest::class;
    protected static ?string $navigationGroup = 'For Approval (Treasury)'; // This is for approval of treasury department
    protected static ?string $slug            = 'payment-processing';
    protected static ?string $navigationLabel = 'Payment Process';
    protected static ?string $label           = 'Payment Process';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        // ->whereHas('roles', function ($query) {
        //     $query->where('name', 'User');
        // })
            ->where('status', StatusRemarks::FOR_PAYMENT_PROCESSING->value);
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
                SpatieMediaLibraryImageColumn::make('attachment')
                    ->collection('attachments'),

                TextColumn::make('request_no')
                    ->label('Request No.')
                    ->sortable()
                    ->searchable()
                    ->url(fn($record) => route('filament.admin.resources.for-approval-requests.view', $record)),

                TextColumn::make('user.name')
                    ->label('Requestor')
                    ->searchable(),

                TextColumn::make('activity_name')
                    ->label('Activity Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('activity_date')
                    ->label('Activity Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('nature_of_request')
                    ->label('Nature of Request')
                    ->sortable()
                    ->badge(),

                TextColumn::make('requesting_amount')
                    ->label('Requesting Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
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
                Tables\Actions\EditAction::make(),
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
            'index'  => Pages\ListPaymentProcesses::route('/'),
            'create' => Pages\CreatePaymentProcess::route('/create'),
            'edit'   => Pages\EditPaymentProcess::route('/{record}/edit'),
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
