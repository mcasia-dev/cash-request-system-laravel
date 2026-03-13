<?php

namespace App\Filament\Resources;

use App\Enums\CashRequest\NatureOfRequestEnum;
use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\ActivityListResource\Pages\CreateActivityListWithTable;
use App\Filament\Resources\CashRequestResource\Pages;
use App\Models\CashRequest\CashRequest;
use App\Services\Ocr\OcrSpaceService;
use Facades\App\Services\CashRequest\CashRequestService;
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
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CashRequestResource extends Resource
{
    protected static ?string $model = CashRequest::class;
    protected static ?string $navigationGroup = 'Cash Requests';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected ?string $pollingInterval = '5s';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
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
                    ->collection('attachments')
                    ->required(),

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
                        NatureOfRequestEnum::PETTY_CASH->value => 'primary',
                        NatureOfRequestEnum::CASH_ADVANCE->value => 'success',
                        default => 'secondary'
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date_liquidated')
                    ->label('Date Liquidated')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_released')
                    ->label('Date Released')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('due_date')
                    ->label('Liquidation Due Date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Status::PENDING->value => 'warning',
                        Status::IN_PROGRESS->value => 'info',
                        Status::APPROVED->value => 'success',
                        Status::RELEASED->value => 'primary',
                        Status::LIQUIDATED->value => 'gray',
                        Status::REJECTED->value => 'danger',
                        Status::CANCELLED->value => 'gray',
                        default => 'secondary',
                    })
                    ->searchable(),

                TextColumn::make('status_remarks')
                    ->label('Status Remarks')
                    ->badge()
                    ->color('secondary')
                    ->sortable()
                    ->searchable(),
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
                        ->form(self::getLiquidateForm())
                        ->modalSubmitActionLabel('Submit')
                        ->action(CashRequestService::getLiquidateAction())
                        ->visible(fn($record) => $record->status === Status::RELEASED->value && $record->status_remarks == StatusRemarks::FOR_LIQUIDATION->value),

                    ViewAction::make(),

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
                        ->modalHeading('Reject Calabel: sh Request')
                        ->modalSubmitActionLabel('Submit')
                        ->action(CashRequestService::getCancelAction())
                        ->visible(fn($record) => CashRequestService::canCancel($record)),
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
            'index' => Pages\ListCashRequests::route('/'),
            'create' => CreateActivityListWithTable::route('/create'),
            'edit' => Pages\EditCashRequest::route('/{record}/edit'),
            'view' => Pages\ViewCashRequest::route('/{record}/view'),
            'track-status' => Pages\TrackRequestStatus::route('/{record}/track-status'),
            'track-status-text' => Pages\TrackRequestStatusText::route('/{record}/track-status-text'),
        ];
    }

    /**
     * Build the liquidation form schema closure for collecting receipt details.
     * @return \Closure
     */
    public static function getLiquidateForm(): \Closure
    {
        return fn($record) => [
            Repeater::make('liquidation_items')
                ->label('Liquidation Receipts')
                ->addActionLabel('Add another receipt')
                ->minItems(1)
                ->reactive()
                ->schema([
                    FileUpload::make('receipt')
                        ->label('Receipt Image')
                        ->disk('public')
                        ->directory('liquidation-receipts')
                        ->preserveFilenames()
                        ->maxSize(1024)
                        ->helperText('Please upload a clear image of the receipt. The system will attempt to read the receipt number from the image. If it fails, you can try uploading a clearer image. Only one receipt per entry is allowed, and duplicate receipt numbers will be rejected. The maximum file size is 1MB.')
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get, FileUpload $component): void {
                            $isValid = app(OcrSpaceService::class)->getReceiptState($state, $set, $get, $component->getStatePath());

                            if (!$isValid) {
                                $component->state([]);
                            }
                        })
                        ->required(),

                    TextInput::make('receipt_number')
                        ->label('Detected Receipt Number')
                        ->readOnly()
                        ->dehydrated(true),

                    TextInput::make('amount')
                        ->numeric()
                        ->required(),

                    Textarea::make('remarks')
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            ...self::getPlaceholders($record),
        ];
    }

    private static function getPlaceholders($record): array
    {
        return [
            self::getTotalReceiptsPlaceholder($record),
            self::getAmountToLiquidatePlaceholder($record),
            self::getAmountToReimbursePlaceholder($record),
            self::getMissingAmountPlaceholder($record),
        ];
    }

    private static function getTotalReceiptsPlaceholder($record)
    {
        return Placeholder::make('total_receipts')
            ->label('Total Receipt Amount')
            ->content(function (Get $get) {
                $total = collect($get('liquidation_items'))
                    ->sum(fn($item) => (float)($item['amount'] ?? 0));

                return number_format($total, 2, '.', ',');
            });
    }

    private static function getAmountToLiquidatePlaceholder($record)
    {
        return Placeholder::make('amount_to_liquidate')
            ->label('Amount to Liquidate')
            ->content(fn() => number_format((float)$record->requesting_amount, 2, '.', ','));
    }

    private static function getAmountToReimbursePlaceholder($record)
    {
        return Placeholder::make('amount_to_reimburse')
            ->label('Amount to Reimburse')
            ->visible(function (Get $get) use ($record): bool {
                $total = collect($get('liquidation_items'))
                    ->sum(fn($item) => (float)($item['amount'] ?? 0));

                return $total > (float)$record->requesting_amount;
            })
            ->content(function (Get $get) use ($record) {
                $total = collect($get('liquidation_items'))
                    ->sum(fn($item) => (float)($item['amount'] ?? 0));

                $reimburse = $total - (float)$record->requesting_amount;

                $formatted = number_format($reimburse, 2, '.', ',');

                return new HtmlString("<span style=\"color:#16a34a;font-weight:600;\">{$formatted}</span>");
            });
    }

    private static function getMissingAmountPlaceholder($record)
    {
        return Placeholder::make('missing_amount')
            ->label('Cash Return')
            ->visible(function (Get $get) use ($record): bool {
                $total = collect($get('liquidation_items'))
                    ->sum(fn($item) => (float)($item['amount'] ?? 0));

                return $total < (float)$record->requesting_amount;
            })
            ->content(function (Get $get) use ($record) {
                $total = collect($get('liquidation_items'))
                    ->sum(fn($item) => (float)($item['amount'] ?? 0));

                $missing = (float)$record->requesting_amount - $total;

                $formatted = number_format($missing, 2, '.', ',');

                return new HtmlString("<span style=\"color:#dc2626;font-weight:600;\">{$formatted}</span>");
            });
    }
}
