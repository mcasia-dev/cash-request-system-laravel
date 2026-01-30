<?php

namespace App\Filament\Resources\CashRequestResource\Pages;

use App\Filament\Resources\CashRequestResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCashRequest extends ViewRecord
{
    protected static string $resource = CashRequestResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Cash Request Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('request_no')
                            ->label('Request No.'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Requestor'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'released' => 'info',
                                'liquidated' => 'primary',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('nature_of_request')
                            ->badge(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Activity Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('activity_name')
                            ->label('Activity Name'),
                        Infolists\Components\TextEntry::make('activity_date')
                            ->label('Activity Date')
                            ->date(),
                        Infolists\Components\TextEntry::make('activity_venue')
                            ->label('Venue'),
                        Infolists\Components\TextEntry::make('purpose')
                            ->label('Purpose')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Payment Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('requesting_amount')
                            ->label('Requesting Amount')
                            ->money('PHP'),
                        Infolists\Components\TextEntry::make('nature_of_payment')
                            ->label('Payment Type'),
                        Infolists\Components\TextEntry::make('payee'),
                        Infolists\Components\TextEntry::make('payment_to')
                            ->label('Payment To'),
                        Infolists\Components\TextEntry::make('bank_name')
                            ->label('Bank'),
                        Infolists\Components\TextEntry::make('bank_account_no')
                            ->label('Account Number'),
                        Infolists\Components\TextEntry::make('account_type')
                            ->label('Account Type'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date(),
                        Infolists\Components\TextEntry::make('date_released')
                            ->label('Date Released')
                            ->date(),
                        Infolists\Components\TextEntry::make('date_liquidated')
                            ->label('Date Liquidated')
                            ->date(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
