<?php
namespace App\Filament\Resources;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Enums\NatureOfRequestEnum;
use App\Filament\Resources\ActivityListResource\Pages\CreateActivityListWithTable;
use App\Filament\Resources\CashRequestResource\Pages;
use App\Models\CashRequest;
use App\Models\ForLiquidation;
use App\Models\LiquidationReceipt;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class CashRequestResource extends Resource
{
    protected static ?string $model           = CashRequest::class;
    protected static ?string $navigationGroup = 'Cash Requests';
    protected static ?string $navigationIcon  = 'heroicon-o-rectangle-stack';

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
                    ->minDate(now()->toDateString())
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
                TextColumn::make('request_no')
                    ->label('Request No.')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('requesting_amount')
                    ->label('Requesting Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('nature_of_request')
                    ->label('Nature of Request')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        NatureOfRequestEnum::PETTY_CASH->value   => 'primary',
                        NatureOfRequestEnum::CASH_ADVANCE->value => 'success',
                        default                                  => 'secondary'
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date_liquidated')
                    ->label('Date Liquidated')
                    ->date()
                    ->sortable(),

                TextColumn::make('date_released')
                    ->label('Date Released')
                    ->date()
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
                        Status::RELEASED->value   => 'info',
                        default                   => 'secondary',
                    })
                    ->searchable(),

                TextColumn::make('status_remarks')
                    ->label('Status Remarks')
                    ->badge()
                    ->color('secondary')
                    ->sortable()
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
                SelectFilter::make('status')
                    ->options(Status::filamentOptions())
                    ->attribute('status'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('activity_timeline')
                        ->label('Track Status')
                        ->color('warning')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->url(fn($record) => route('filament.admin.resources.cash-requests.track-status', ['record' => $record])),

                    Action::make('liquidate')
                        ->icon('heroicon-o-banknotes')
                        ->color('info')
                        ->form(fn($record) => [
                            Repeater::make('liquidation_items')
                                ->label('Liquidation Receipts')
                                ->addActionLabel('Add another receipt')
                                ->minItems(1)
                                ->reactive()
                                ->schema([
                                    FileUpload::make('receipt')
                                        ->label('Upload Receipt')
                                        ->disk('public')
                                        ->directory('liquidation-receipts')
                                        ->preserveFilenames()
                                        ->required(),

                                    TextInput::make('amount')
                                        ->numeric()
                                        ->required(),

                                    Textarea::make('remarks')
                                        ->nullable()
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),

                            Placeholder::make('total_receipts')
                                ->label('Total Receipt Amount')
                                ->content(function (Get $get) {
                                    $total = collect($get('liquidation_items'))
                                        ->sum(fn($item) => (float) ($item['amount'] ?? 0));

                                    return number_format($total, 2, '.', ',');
                                }),

                            Placeholder::make('amount_to_liquidate')
                                ->label('Amount to Liquidate'),

                            Placeholder::make('amount_to_reimburse')
                                ->label('Amount to Reimburse')
                                ->visible(function (Get $get) use ($record): bool {
                                    $total = collect($get('liquidation_items'))
                                        ->sum(fn($item) => (float) ($item['amount'] ?? 0));

                                    return $total > (float) $record->requesting_amount;
                                })
                                ->content(function (Get $get) use ($record) {
                                    $total = collect($get('liquidation_items'))
                                        ->sum(fn($item) => (float) ($item['amount'] ?? 0));

                                    $reimburse = $total - (float) $record->requesting_amount;

                                    $formatted = number_format($reimburse, 2, '.', ',');

                                    return new HtmlString("<span style=\"color:#16a34a;font-weight:600;\">{$formatted}</span>");
                                }),

                            Placeholder::make('missing_amount')
                                ->label('Missing Amount')
                                ->visible(function (Get $get) use ($record): bool {
                                    $total = collect($get('liquidation_items'))
                                        ->sum(fn($item) => (float) ($item['amount'] ?? 0));

                                    return $total < (float) $record->requesting_amount;
                                })
                                ->content(function (Get $get) use ($record) {
                                    $total = collect($get('liquidation_items'))
                                        ->sum(fn($item) => (float) ($item['amount'] ?? 0));

                                    $missing = (float) $record->requesting_amount - $total;

                                    $formatted = number_format($missing, 2, '.', ',');

                                    return new HtmlString("<span style=\"color:#dc2626;font-weight:600;\">{$formatted}</span>");
                                }),
                        ])
                        ->modalSubmitActionLabel('Submit')
                        ->action(function ($record, array $data) {
                            $user           = Auth::user();
                            $previousStatus = $record->status;

                            $totalReceipts = collect($data['liquidation_items'] ?? [])
                                ->sum(fn($item) => (float) ($item['amount'] ?? 0));

                            $requestingAmount  = (float) $record->requesting_amount;
                            $amountToReimburse = $totalReceipts > $requestingAmount
                                ? $totalReceipts - $requestingAmount
                                : 0.0;
                            $missingAmount = $totalReceipts < $requestingAmount
                                ? $requestingAmount - $totalReceipts
                                : 0.0;

                            $liquidation = ForLiquidation::firstOrCreate([
                                'cash_request_id' => $record->id,
                            ], [
                                'total_liquidated' => $totalReceipts,
                                'total_change'     => $amountToReimburse,
                                'missing_amount'   => $missingAmount,
                            ]);

                            if (! $liquidation->wasRecentlyCreated) {
                                $liquidation->update([
                                    'total_change'   => $amountToReimburse,
                                    'missing_amount' => $missingAmount,
                                    'receipt_amount' => $totalReceipts,
                                ]);
                            }

                            foreach ($data['liquidation_items'] as $item) {
                                $receipt = LiquidationReceipt::create([
                                    'liquidation_id' => $liquidation->id,
                                    'receipt_amount' => $item['amount'],
                                    'remarks'        => $item['remarks'] ?? null,
                                ]);

                                if (! empty($item['receipt'])) {
                                    $path = $item['receipt'];

                                    $receipt
                                        ->addMedia(Storage::disk('public')->path($path))
                                        ->toMediaCollection('liquidation-receipts');
                                }
                            }

                            $record->update([
                                'status'          => Status::LIQUIDATED->value,
                                'status_remarks'  => StatusRemarks::LIQUIDATED->value,
                                'date_liquidated' => Carbon::now(),
                            ]);

                            activity()
                                ->causedBy($user)
                                ->performedOn($record)
                                ->event('liquidated')
                                ->withProperties([
                                    'request_no'        => $record->request_no,
                                    'activity_name'     => $record->activity_name,
                                    'requesting_amount' => $record->requesting_amount,
                                    'previous_status'   => $previousStatus,
                                    'new_status'        => Status::LIQUIDATED->value,
                                    'status_remarks'    => StatusRemarks::LIQUIDATED->value,
                                ])
                                ->log("Cash request {$record->request_no} was liquidated by {$user->name}");

                            Notification::make()
                                ->title('Cash Request Liquidated!')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->status === Status::RELEASED->value),

                    ViewAction::make(),

                    EditAction::make()
                        ->visible(fn() => Status::PENDING->value),

                    DeleteAction::make()
                        ->visible(fn($record) => Status::PENDING->value && $record->status_remarks == null),

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
                            $user = Auth::user();
                            $record->update([
                                'status'                => Status::CANCELLED->value,
                                'reason_for_cancelling' => $data['reason_for_cancelling'],
                            ]);

                            activity()
                                ->causedBy($user)
                                ->performedOn($record)
                                ->event('cancelled')
                                ->withProperties([
                                    'request_no'            => $record->request_no,
                                    'activity_name'         => $record->activity_name,
                                    'requesting_amount'     => $record->requesting_amount,
                                    'previous_status'       => Status::PENDING->value,
                                    'new_status'            => Status::CANCELLED->value,
                                    'reason_for_cancelling' => $data['reason_for_cancelling'],
                                ])
                                ->log("Cash request {$record->request_no} was cancelled by {$user->name}");

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
            'index'             => Pages\ListCashRequests::route('/'),
            'create'            => CreateActivityListWithTable::route('/create'),
            'edit'              => Pages\EditCashRequest::route('/{record}/edit'),
            'view'              => Pages\ViewCashRequest::route('/{record}/view'),
            'track-status'      => Pages\TrackRequestStatus::route('/{record}/track-status'),
            'track-status-text' => Pages\TrackRequestStatusText::route('/{record}/track-status-text'),
        ];
    }
}
