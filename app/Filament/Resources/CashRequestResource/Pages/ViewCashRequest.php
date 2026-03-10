<?php

namespace App\Filament\Resources\CashRequestResource\Pages;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\CashRequestResource;
use App\Models\LiquidationReceipt;
use App\Models\PaymentProcess;
use Carbon\Carbon;
use Facades\App\Services\CashRequest\CashRequestService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Njxqlus\Filament\Components\Infolists\LightboxSpatieMediaLibraryImageEntry;

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

                        TextEntry::make('nature_of_request')
                            ->label('Nature of Request')
                            ->badge(),

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
                                'pending' => 'warning',
                                'approved' => 'success',
                                'released' => 'info',
                                'liquidated' => 'primary',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('status_remarks')
                            ->label('Status Remarks')
                            ->badge()
                            ->color(fn($record): string => match ($record->status) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'released' => 'info',
                                'liquidated' => 'primary',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('reason_for_rejection')
                            ->label('Reason for Rejection')
                            ->visible(fn($record) => $record->status === Status::REJECTED->value),

                        TextEntry::make('reason_for_cancelling')
                            ->label('Reason for Cancelling')
                            ->visible(fn($record) => $record->status === Status::CANCELLED->value),
                    ])
                    ->columns(3),

                Section::make('Activity Information')
                    ->collapsible()
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

                                TextEntry::make('requesting_amount')
                                    ->label('Requesting Amount')
                                    ->money('PHP'),

                                LightboxSpatieMediaLibraryImageEntry::make('attachment')
                                    ->label('Attached File/Images')
                                    ->collection('attachments')
                                    ->columnSpanFull(),

                                TextEntry::make('status')
                                    ->label('Activity Status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'rejected' => 'danger',
                                        'approved' => 'success',
                                        'pending' => 'warning',
                                        default => 'gray',
                                    }),

                                TextEntry::make('rejection_remarks')
                                    ->label('Reason for Rejection')
                                    ->visible(fn($record) => filled($record->rejection_remarks))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),

//                Section::make('Payment Details')
//                    ->collapsible()
//                    ->collapsed()
//                    ->schema([
//                        TextEntry::make('nature_of_payment')
//                            ->label('Payment Type'),
//
//                        TextEntry::make('payee'),
//
//                        TextEntry::make('payment_to')
//                            ->label('Payment To'),
//
//                        TextEntry::make('bank_name')
//                            ->label('Bank'),
//
//                        TextEntry::make('bank_account_no')
//                            ->label('Account Number'),
//
//                        TextEntry::make('account_type')
//                            ->label('Account Type'),
//
//                        TextEntry::make('cc_holder_name')
//                            ->label('Card Holder Name')
//                            ->visible(fn($record) => filled($record->cc_holder_name)),
//
//                        TextEntry::make('cc_number')
//                            ->label('Card Number')
//                            ->visible(fn($record) => filled($record->cc_number)),
//
//                        TextEntry::make('cc_type')
//                            ->label('Card Type')
//                            ->visible(fn($record) => filled($record->cc_type)),
//
//                        TextEntry::make('cc_expiration')
//                            ->label('Card Expiration')
//                            ->visible(fn($record) => filled($record->cc_expiration)),
//                    ])
//                    ->columns(2),

                Section::make('Approval and Processing')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('approved_by')
                            ->label('Approved By')
                            ->state(function ($record) {
                                $activity = CashRequestService::getLatestActivity(PaymentProcess::find($record->id), 'approved');

                                return $activity?->causer?->name ?? 'N/A';
                            }),

                        TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->state(fn($record) => CashRequestService::getLatestActivity(PaymentProcess::find($record->id), 'approved')?->created_at)
                            ->dateTime('F d, Y - h:i A'),

                        TextEntry::make('processed_by')
                            ->label('Processed By')
                            ->state(fn($record) => $record->forCashRelease?->processedBy?->name ?? 'N/A'),

                        TextEntry::make('date_processed')
                            ->label('Date Processed')
                            ->state(fn($record) => $record->forCashRelease?->date_processed)
                            ->dateTime('F d, Y - h:i A'),
                    ])
                    ->columns(2),

                Section::make('Dates')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('forCashRelease.releasing_date')
                            ->label('Releasing Date')
                            ->formatStateUsing(function ($record) {
                                return "{$record->forCashRelease?->releasing_date?->format('F d, Y')} "
                                    . Carbon::parse($record->forCashRelease?->releasing_time_from)?->format('h:i A')
                                    . ' - '
                                    . Carbon::parse($record->forCashRelease?->releasing_time_to)?->format('h:i A');
                            }),

                        TextEntry::make('due_date')
                            ->label('Liquidation Due Date')
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
                            ->state(fn($record) => CashRequestService::getLiquidationFor($record)?->total_liquidated)
                            ->money('PHP'),

                        TextEntry::make('total_change')
                            ->label('Total Change')
                            ->state(fn($record) => CashRequestService::getLiquidationFor($record)?->total_change)
                            ->money('PHP'),

                        TextEntry::make('missing_amount')
                            ->label('Missing Amount')
                            ->state(fn($record) => CashRequestService::getLiquidationFor($record)?->missing_amount)
                            ->money('PHP'),

                        TextEntry::make('liquidated_by')
                            ->label('Liquidated By')
                            ->state(function ($record) {
                                $activity = CashRequestService::getLatestActivity($record, 'liquidated');

                                return $activity?->causer?->name ?? 'N/A';
                            }),

                        TextEntry::make('liquidated_at')
                            ->label('Liquidated At')
                            ->state(fn($record) => CashRequestService::getLatestActivity($record, 'liquidated')?->created_at)
                            ->dateTime('F d, Y - h:i A'),

                        TextEntry::make('total_receipts')
                            ->label('Total Receipts')
                            ->state(function ($record) {
                                $liquidation = CashRequestService::getLiquidationFor($record);

                                return $liquidation
                                    ? LiquidationReceipt::where('liquidation_id', $liquidation->id)->sum('receipt_amount')
                                    : 0;
                            })
                            ->money('PHP'),

                        TextEntry::make('liquidation_remarks')
                            ->label('Liquidation Remarks')
                            ->state(fn($record) => CashRequestService::getLiquidationFor($record)?->remarks)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn($record) => CashRequestService::getLiquidationFor($record) !== null),
            ]);
    }
}
