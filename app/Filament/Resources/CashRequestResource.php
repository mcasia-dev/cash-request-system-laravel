<?php

namespace App\Filament\Resources;

use App\Enums\CashRequest\Status;
use App\Enums\NatureOfRequestEnum;
use App\Filament\Resources\ActivityListResource\Pages\CreateActivityListWithTable;
use App\Filament\Resources\CashRequestResource\Pages;
use App\Models\CashRequest;
use App\Services\CashRequest\CancellationService;
use App\Services\CashRequest\LiquidationService;
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
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mayaram\LaravelOcr\Facades\LaravelOcr;

class CashRequestResource extends Resource
{
    protected static ?string $model = CashRequest::class;
    protected static ?string $navigationGroup = 'Cash Requests';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

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
                TextColumn::make('user.name')
                    ->label('Requestor'),

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
                    ->sortable(),

                TextColumn::make('date_released')
                    ->label('Date Released')
                    ->date()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Liquidation Due Date')
                    ->date()
                    ->sortable(),

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
                        ->form(self::getLiquidateForm())
                        ->modalSubmitActionLabel('Submit')
                        ->action(self::getLiquidateAction())
                        ->visible(fn($record) => $record->status === Status::RELEASED->value),

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
                        ->modalHeading('Reject Cash Request')
                        ->modalSubmitActionLabel('Submit')
                        ->action(self::getCancelAction())
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
                        ->label('Upload Receipt')
                        ->disk('public')
                        ->directory('liquidation-receipts')
                        ->preserveFilenames()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $receiptNo = self::extractReceiptNo($state);
                            $set('detected_receipt_no', $receiptNo);
                        })
                        ->required(),

                    TextInput::make('detected_receipt_no')
                        ->label('Detected Receipt No.')
                        ->readOnly()
                        ->dehydrated(false)
                        ->helperText('Auto-detected from OCR after upload.'),

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
                        ->sum(fn($item) => (float)($item['amount'] ?? 0));

                    return number_format($total, 2, '.', ',');
                }),

            Placeholder::make('amount_to_liquidate')
                ->label('Amount to Liquidate'),

            Placeholder::make('amount_to_reimburse')
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
                }),

            Placeholder::make('missing_amount')
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
                }),
        ];
    }

    /**
     * Build the liquidation action closure to save receipts and update status.
     * @return \Closure
     */
    public static function getLiquidateAction(): \Closure
    {
        return function ($record, array $data) {
            app(LiquidationService::class)->liquidate($record, $data, Auth::user());
        };
    }

    /**
     * Build the cancel action closure to mark a request as cancelled.
     * @return \Closure
     */
    public static function getCancelAction(): \Closure
    {
        return function ($record, array $data) {
            app(CancellationService::class)->cancel($record, $data, Auth::user());
        };
    }

    private static function extractReceiptNo(mixed $uploadedPath): ?string
    {
        $file = self::resolveUploadedFilePath($uploadedPath);

        if ($file === null || !is_file($file)) {
            Log::warning('OCR skipped: invalid file path', ['uploaded_path' => $uploadedPath]);
            return null;
        }

        try {
            Log::info('OCR started for liquidation receipt upload', [
                'uploaded_path' => $uploadedPath,
                'resolved_path' => $file,
            ]);

            $result = LaravelOcr::extract($file, [
                'language' => 'eng',
//                'psm' => 6,
                'extract_line_items' => true,
                'use_ai_cleanup' => true,
            ]);

//            dd($result);

            $text = is_array($result) ? ($result['text'] ?? '') : '';
            $receiptNo = self::extractReceiptReferenceFromText((string)$text);

            Log::info('OCR completed for liquidation receipt upload', [
                'uploaded_path' => $uploadedPath,
                'detected_order_ref' => $receiptNo,
                'ocr_text_sample' => mb_substr(preg_replace('/\s+/', ' ', (string)$text), 0, 180),
            ]);

            return $receiptNo;
        } catch (\Throwable $e) {
            Log::error('OCR failed for liquidation receipt upload', [
                'uploaded_path' => $uploadedPath,
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]);
            return null;
        }
    }

    private static function resolveUploadedFilePath(mixed $uploadedPath): ?string
    {
        if ($uploadedPath instanceof TemporaryUploadedFile || $uploadedPath instanceof UploadedFile) {
            $path = $uploadedPath->getRealPath();
            return is_string($path) && $path !== '' ? $path : null;
        }

        if (is_string($uploadedPath) && trim($uploadedPath) !== '') {
            return Storage::disk('public')->path($uploadedPath);
        }

        return null;
    }

    private static function extractReceiptReferenceFromText(string $text): ?string
    {
        $normalizedText = strtoupper(str_replace("\r", '', $text));
        $lines = preg_split('/\n+/', $normalizedText) ?: [];

        $patterns = [
            '/\bINVOICE\s*#?\s*([A-Z0-9\-]{5,})\b/',
            '/\b(?:OR|O\.R\.|OFFICIAL\s+RECEIPT)\s*#?\s*([A-Z0-9\-]{5,})\b/',
            '/\b(?:RECEIPT|REFERENCE|REF)\s*(?:NO|NUMBER)?\s*#?\s*([A-Z0-9\-]{5,})\b/',
        ];

        foreach ($lines as $line) {
            $cleanLine = preg_replace('/\s+/', ' ', trim($line)) ?? '';
            if ($cleanLine === '' || str_contains($cleanLine, 'CARD') || str_contains($cleanLine, 'FEEDBACK')) {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $cleanLine, $matches) === 1) {
                    $candidate = preg_replace('/[^A-Z0-9\-]/', '', trim($matches[1])) ?? '';
                    return $candidate !== '' ? $candidate : null;
                }
            }
        }

        // Fallback: if OCR is noisy, grab a long numeric token likely to be invoice id.
        if (preg_match('/\b(\d{8,14})\b/', $normalizedText, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
