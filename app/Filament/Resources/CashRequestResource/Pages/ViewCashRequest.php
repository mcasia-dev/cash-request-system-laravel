<?php
namespace App\Filament\Resources\CashRequestResource\Pages;

use App\Filament\Resources\CashRequestResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

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

                        TextEntry::make('nature_of_request')
                            ->badge(),
                    ])
                    ->columns(2),

                Section::make('Activity Information')
                    ->schema([
                        TextEntry::make('activity_name')
                            ->label('Activity Name'),

                        TextEntry::make('activity_date')
                            ->label('Activity Date')
                            ->date(),

                        TextEntry::make('activity_venue')
                            ->label('Venue'),

                        TextEntry::make('purpose')
                            ->label('Purpose')
                            ->columnSpanFull(),

                        SpatieMediaLibraryImageEntry::make('attachment')
                            ->collection('attachments'),
                    ])
                    ->columns(2),

                Section::make('Payment Details')
                    ->schema([
                        TextEntry::make('requesting_amount')
                            ->label('Requesting Amount')
                            ->money('PHP'),

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
                    ])
                    ->columns(2),

                Section::make('Dates')
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

                Section::make('Additional Information')
                    ->schema([
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
}
