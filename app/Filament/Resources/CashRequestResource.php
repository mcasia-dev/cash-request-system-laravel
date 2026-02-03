<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CashRequest;
use Filament\Resources\Resource;
use App\Enums\CashRequest\Status;
use App\Enums\NatureOfRequestEnum;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CashRequestResource\Pages;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\ActivityListResource\Pages\CreateActivityListWithTable;

class CashRequestResource extends Resource
{
    protected static ?string $model = CashRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('user_id')
                    ->default(Auth::id()),

                Select::make('nature_of_request')
                    ->options(NatureOfRequestEnum::filamentOptions())
                    ->live()
                    ->required(),

                TextInput::make('activity_name')
                    ->label('Activity Name')
                    ->required(),

                DatePicker::make('activity_date')
                    ->label('Activity Date')
                    ->minDate(now())
                    ->required(),

                TextInput::make('activity_venue')
                    ->label('Activity Venue')
                    ->required(),

                TextInput::make('requesting_amount')
                    ->label('Requesting Amount')
                    ->prefix('₱')
                    ->required()
                    ->numeric()
                    ->maxValue(fn($get) => $get('nature_of_request') === NatureOfRequestEnum::PETTY_CASH->value ? 1500 : null),

                SpatieMediaLibraryFileUpload::make('attachment')
                    ->collection('attachments'),

                Textarea::make('purpose')
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('attachment')
                    ->collection('attachments'),

                TextColumn::make('request_no')
                    ->sortable()
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
                Tables\Actions\Action::make('activity_timeline')
                    ->label('Track Status')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->url(fn($record) => route('filament.admin.resources.cash-requests.track-status', ['record' => $record])),

                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn() => Status::PENDING->value),

                    Action::make('cancel')
                        ->color('danger')
                        ->form([
                            Textarea::make('reason_for_cancelling')
                                ->label('Reason for Cancelling')
                                ->required()
                                ->maxLength(65535),
                        ])
                        ->icon('heroicon-o-x-circle')
                        ->modalHeading('Reject Cash Request')
                        ->modalSubmitActionLabel('Submit')
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status'                => Status::CANCELLED->value,
                                'reason_for_cancelling' => $data['reason_for_cancelling'],
                            ]);

                            activity()
                                ->causedBy(Auth::user())
                                ->performedOn($record)
                                ->event('cancelled')
                                ->withProperties([
                                    'request_no'            => $record->request_no,
                                    'activity_name'         => $record->activity_name,
                                    'requesting_amount'     => $record->requesting_amount,
                                    'previous_status'       => 'pending',
                                    'new_status'            => 'cancelled',
                                    'reason_for_cancelling' => $data['reason_for_cancelling'],
                                ])
                                ->log("Cash request {$record->request_no} was cancelled by " . Auth::user()->name);

                            Notification::make()
                                ->title('Cash Request Cancelled!')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->status === Status::PENDING->value),
                ]),

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
            'index'        => Pages\ListCashRequests::route('/'),
            'create'       => CreateActivityListWithTable::route('/create'),
            'edit'         => Pages\EditCashRequest::route('/{record}/edit'),
            'view'         => Pages\ViewCashRequest::route('/{record}/view'),
            'track-status' => Pages\TrackRequestStatus::route('/{record}/track-status'),
        ];
    }
}
