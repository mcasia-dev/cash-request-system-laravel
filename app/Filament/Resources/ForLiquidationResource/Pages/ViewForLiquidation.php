<?php
namespace App\Filament\Resources\ForLiquidationResource\Pages;

use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\ForLiquidationResource;

class ViewForLiquidation extends ViewRecord
{
    protected static string $resource = ForLiquidationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Cash Request Details')
                    ->schema([
                        TextEntry::make('cashRequest.request_no')
                            ->label('Request No.'),

                        TextEntry::make('cashRequest.user.name')
                            ->label('Requestor'),

                        TextEntry::make('cashRequest.status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending'    => 'warning',
                                'approved'   => 'success',
                                'released'   => 'info',
                                'liquidated' => 'primary',
                                'rejected'   => 'danger',
                                default      => 'gray',
                            }),

                        TextEntry::make('cashRequest.nature_of_request')
                            ->badge(),
                    ])
                    ->columns(2),

                Section::make('Activity Information')
                    ->schema([
                        TextEntry::make('cashRequest.activity_name')
                            ->label('Activity Name'),

                        TextEntry::make('cashRequest.activity_date')
                            ->label('Activity Date')
                            ->date(),

                        TextEntry::make('cashRequest.activity_venue')
                            ->label('Venue'),

                        TextEntry::make('cashRequest.purpose')
                            ->label('Purpose')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Payment Details')
                    ->schema([
                        TextEntry::make('cashRequest.requesting_amount')
                            ->label('Requesting Amount')
                            ->money('PHP'),

                        TextEntry::make('cashRequest.nature_of_payment')
                            ->label('Payment Type'),

                        TextEntry::make('cashRequest.payee'),

                        TextEntry::make('cashRequest.payment_to')
                            ->label('Payment To'),

                        TextEntry::make('cashRequest.bank_name')
                            ->label('Bank'),

                        TextEntry::make('cashRequest.bank_account_no')
                            ->label('Account Number'),

                        TextEntry::make('cashRequest.account_type')
                            ->label('Account Type'),

                    ])
                    ->columns(2),

                Section::make('Release Processing')
                    ->schema([
                        TextEntry::make('cashRequest.forCashRelease.processedBy.name')
                            ->label('Processed By'),

                        TextEntry::make('cashRequest.forCashRelease.releasedBy.name')
                            ->label('Released By'),
                    ])
                    ->columns(2),

                Section::make('Liquidation Details')
                    ->schema([
                        TextEntry::make('receipt_amount')
                            ->label('Receipt Amount')
                            ->money('PHP'),

                        TextEntry::make('total_user')
                            ->label('Total Used')
                            ->money('PHP'),

                        TextEntry::make('total_liquidated')
                            ->label('Total Liquidated')
                            ->money('PHP'),

                        TextEntry::make('total_change')
                            ->label('Total Change')
                            ->money('PHP'),

                        TextEntry::make('missing_amount')
                            ->label('Missing Amount')
                            ->money('PHP'),

                        TextEntry::make('aging')
                            ->label('Aging (Days)'),

                        TextEntry::make('remarks')
                            ->label('Remarks')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Dates')
                    ->schema([
                        TextEntry::make('cashRequest.created_at')
                            ->label('Date Requested')
                            ->date(),
                        TextEntry::make('releasing_date')
                            ->label('Releasing Date')
                            ->date(),

                        TextEntry::make('cashRequest.due_date')
                            ->label('Due Date')
                            ->date(),

                        TextEntry::make('cashRequest.date_released')
                            ->label('Date Released')
                            ->date(),

                        TextEntry::make('cashRequest.date_liquidated')
                            ->label('Date Liquidated')
                            ->date(),
                    ])
                    ->columns(3),
            ]);
    }
}
