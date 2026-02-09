<?php
namespace App\Filament\Resources\ForLiquidationResource\Pages;

use App\Filament\Resources\ForLiquidationResource;
use App\Models\ForLiquidation;
use App\Models\LiquidationReceipt;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

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

                Section::make('Receipt Images')
                    ->schema([
                        TextEntry::make('receipt_images')
                            ->label('Receipts')
                            ->state(function (ForLiquidation $record) {
                                $urls = $this->getReceiptImageUrls($record);

                                if (empty($urls)) {
                                    return 'No receipt images uploaded.';
                                }

                                $html = '<div style="display:flex;flex-wrap:wrap;gap:10px;">';

                                foreach ($urls as $url) {
                                    $safeUrl = e($url);

                                    $html .= '<a href="'
                                        . $safeUrl
                                        . '" target="_blank" rel="noopener noreferrer">'
                                        . '<img src="'
                                        . $safeUrl
                                        . '" alt="Receipt image" style="max-width:180px;max-height:180px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;" />'
                                        . '</a>';
                                }

                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull()
                            ->html(),
                    ])
                    ->visible(fn(ForLiquidation $record) => ! empty($this->getReceiptImageUrls($record))),

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

    /**
     * Get all receipt image URLs for a liquidation record, cached by liquidation ID.
     *
     * @param ForLiquidation $record
     * @return array<int, string>
     */
    private function getReceiptImageUrls(ForLiquidation $record): array
    {
        static $cache = [];

        if (! array_key_exists($record->id, $cache)) {
            $cache[$record->id] = LiquidationReceipt::query()
                ->where('liquidation_id', $record->id)
                ->get()
                ->flatMap(fn(LiquidationReceipt $receipt) => $receipt->getMedia('liquidation-receipts'))
                ->map(fn($media) => $media->getUrl())
                ->filter()
                ->values()
                ->all();
        }

        return $cache[$record->id];
    }
}
