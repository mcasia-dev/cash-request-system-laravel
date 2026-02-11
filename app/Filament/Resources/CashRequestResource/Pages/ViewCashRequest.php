<?php
namespace App\Filament\Resources\CashRequestResource\Pages;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\CashRequestResource;
use App\Models\CashRequest;
use App\Models\ForLiquidation;
use App\Models\LiquidationReceipt;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Spatie\Activitylog\Models\Activity;

class ViewCashRequest extends ViewRecord
{
    protected static string $resource = CashRequestResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Cash Request Details')
                    ->schema([
                        TextEntry::make('request_no')
                            ->label('Request No.'),

                        TextEntry::make('user.name')
                            ->label('Requestor'),

                        TextEntry::make('requesting_amount')
                            ->label('Total Requesting Amount')
                            ->money('PHP'),

                        TextEntry::make('created_at')
                            ->label('Date Submitted')
                            ->dateTime('F d, Y h:i A'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending'    => 'warning',
                                'approved'   => 'success',
                                'released'   => 'info',
                                'liquidated' => 'primary',
                                'rejected'   => 'danger',
                                default      => 'gray',
                            }),
                    ])
                    ->columns(3),

                Section::make('Activity Information')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('activityLists')
                            ->label('')
                            ->schema([
                                TextEntry::make('activity_name')
                                    ->label('Activity Name'),

                                TextEntry::make('activity_date')
                                    ->label('Activity Date')
                                    ->date(),

                                TextEntry::make('activity_venue')
                                    ->label('Venue'),

                                TextEntry::make('purpose')
                                    ->label('Purpose'),

                                TextEntry::make('nature_of_request')
                                    ->label('Nature of Request')
                                    ->badge(),

                                TextEntry::make('requesting_amount')
                                    ->label('Requesting Amount')
                                    ->money('PHP'),

                                SpatieMediaLibraryImageEntry::make('attachment')
                                    ->label('Attached File/Image')
                                    ->collection('attachments')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Payment Details')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('nature_of_payment')
                            ->label('Payment Type'),

                        TextEntry::make('payee'),

                        TextEntry::make('payment_to')
                            ->label('Payment To'),

                        TextEntry::make('bank_name')
                            ->label('Bank'),

                        TextEntry::make('bank_account_no')
                            ->label('Account Number'),

                        TextEntry::make('account_type')
                            ->label('Account Type'),

                        TextEntry::make('cc_holder_name')
                            ->label('Card Holder Name')
                            ->visible(fn($record) => filled($record->cc_holder_name)),

                        TextEntry::make('cc_number')
                            ->label('Card Number')
                            ->visible(fn($record) => filled($record->cc_number)),

                        TextEntry::make('cc_type')
                            ->label('Card Type')
                            ->visible(fn($record) => filled($record->cc_type)),

                        TextEntry::make('cc_expiration')
                            ->label('Card Expiration')
                            ->visible(fn($record) => filled($record->cc_expiration)),
                    ])
                    ->columns(2),

                Section::make('Approval and Processing')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('approved_by')
                            ->label('Approved By')
                            ->state(function ($record) {
                                $activity = $this->getLatestActivity($record, 'approved');

                                return $activity?->causer?->name ?? 'N/A';
                            }),

                        TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->state(fn($record) => $this->getLatestActivity($record, 'approved')?->created_at)
                            ->dateTime(),

                        TextEntry::make('processed_by')
                            ->label('Processed By')
                            ->state(fn($record) => $record->forCashRelease?->processedBy?->name ?? 'N/A'),

                        TextEntry::make('date_processed')
                            ->label('Date Processed')
                            ->state(fn($record) => $record->forCashRelease?->date_processed)
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Dates')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date(),

                        TextEntry::make('date_released')
                            ->label('Date Released')
                            ->date(),

                        TextEntry::make('date_liquidated')
                            ->label('Date Liquidated')
                            ->date(),
                    ])
                    ->columns(3),

                Section::make('Liquidation Details')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('total_liquidated')
                            ->label('Total Liquidated')
                            ->state(fn($record) => $this->getLiquidationFor($record)?->total_liquidated)
                            ->money('PHP'),

                        TextEntry::make('total_change')
                            ->label('Total Change')
                            ->state(fn($record) => $this->getLiquidationFor($record)?->total_change)
                            ->money('PHP'),

                        TextEntry::make('missing_amount')
                            ->label('Missing Amount')
                            ->state(fn($record) => $this->getLiquidationFor($record)?->missing_amount)
                            ->money('PHP'),

                        TextEntry::make('liquidated_by')
                            ->label('Liquidated By')
                            ->state(function ($record) {
                                $activity = $this->getLatestActivity($record, 'liquidated');

                                return $activity?->causer?->name ?? 'N/A';
                            }),

                        TextEntry::make('liquidated_at')
                            ->label('Liquidated At')
                            ->state(fn($record) => $this->getLatestActivity($record, 'liquidated')?->created_at)
                            ->dateTime(),

                        TextEntry::make('receipt_count')
                            ->label('Receipt Count')
                            ->state(function ($record) {
                                $liquidation = $this->getLiquidationFor($record);

                                return $liquidation
                                    ? LiquidationReceipt::where('liquidation_id', $liquidation->id)->count()
                                    : 0;
                            }),

                        TextEntry::make('total_receipts')
                            ->label('Total Receipts')
                            ->state(function ($record) {
                                $liquidation = $this->getLiquidationFor($record);

                                return $liquidation
                                    ? LiquidationReceipt::where('liquidation_id', $liquidation->id)->sum('receipt_amount')
                                    : 0;
                            })
                            ->money('PHP'),

                        TextEntry::make('liquidation_remarks')
                            ->label('Liquidation Remarks')
                            ->state(fn($record) => $this->getLiquidationFor($record)?->remarks)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn($record) => $this->getLiquidationFor($record) !== null),

                Section::make('Additional Information')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('status_remarks')
                            ->label('Status Remarks')
                            ->visible(fn($record) => $record->status != Status::LIQUIDATED->value)
                            ->columnSpanFull(),

                        TextEntry::make('reason_for_rejection')
                            ->label('Reason for Rejection')
                            ->visible(fn($record) => filled($record->reason_for_rejection))
                            ->columnSpanFull(),

                        TextEntry::make('reason_for_cancelling')
                            ->label('Reason for Cancelling')
                            ->visible(fn($record) => filled($record->reason_for_cancelling))
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Get the liquidation record for the given cash request, with a simple in-memory cache.
     *
     * @param CashRequest $record
     * @return ForLiquidation|null
     */
    private function getLiquidationFor(CashRequest $record): ?ForLiquidation
    {
        static $cache = [];

        if (! array_key_exists($record->id, $cache)) {
            $cache[$record->id] = ForLiquidation::where('cash_request_id', $record->id)->first();
        }

        return $cache[$record->id];
    }

    /**
     * Get the most recent activity for the given cash request and event, with a simple cache.
     *
     * @param CashRequest $record
     * @param string $event
     * @return Activity|null
     */
    private function getLatestActivity(CashRequest $record, string $event): ?Activity
    {
        static $cache = [];
        $key          = $record->id . '|' . $event;

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = Activity::query()
                ->where('subject_type', $record::class)
                ->where('subject_id', $record->id)
                ->where('event', $event)
                ->latest('created_at')
                ->with('causer')
                ->first();
        }

        return $cache[$key];
    }
}
