<?php
namespace App\Filament\Resources\ForLiquidationResource\Pages;

use App\Filament\Resources\ForLiquidationResource;
use App\Models\ForLiquidation;
use App\Models\LiquidationReceipt;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
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

                        TextEntry::make('cashRequest.requesting_amount')
                            ->label('Total Requesting Amount')
                            ->money('PHP'),

                        TextEntry::make('cashRequest.status')
                            ->label('Status')
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
                    ->columns(4),

                Section::make('Activity Information')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('cashRequest.activityLists')
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
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('cashRequest.forCashRelease.processedBy.name')
                            ->label('Processed By'),

                        TextEntry::make('cashRequest.forCashRelease.releasedBy.name')
                            ->label('Released By'),
                    ])
                    ->columns(2),

                Section::make('Liquidation Details')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('receipt_amount')
                            ->label('Receipt Amount')
                            ->money('PHP'),

                        // TextEntry::make('total_user')
                        //     ->label('Total Used')
                        //     ->money('PHP'),

                        // TextEntry::make('total_liquidated')
                        //     ->label('Total Liquidated')
                        //     ->money('PHP'),

                        TextEntry::make('total_change')
                            ->label('Amount to Reimburse')
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
                    ->columns(4),

                Section::make('Receipt Images')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('receipt_images')
                            ->label('Receipts')
                            ->state(function (ForLiquidation $record) {
                                $receipts = $this->getReceiptEntries($record);

                                if (empty($receipts)) {
                                    return 'No receipt images uploaded.';
                                }

                                $html = '<div style="display:flex;flex-wrap:wrap;gap:10px;">';

                                foreach ($receipts as $receipt) {
                                    $safeUrl = e($receipt['url']);
                                    $amount = number_format((float) ($receipt['amount'] ?? 0), 2);
                                    $remarks = filled($receipt['remarks']) ? e($receipt['remarks']) : 'N/A';

                                    $html .= '<div style="width:220px;border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#fff;">'
                                        . '<a href="'
                                        . $safeUrl
                                        . '" target="_blank" rel="noopener noreferrer">'
                                        . '<img src="'
                                        . $safeUrl
                                        . '" alt="Receipt image" style="width:100%;max-height:180px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;" />'
                                        . '</a>'
                                        . '<div style="margin-top:8px;font-size:12px;line-height:1.45;">'
                                        . '<div><strong>Amount:</strong> PHP ' . $amount . '</div>'
                                        . '<div><strong>Remarks:</strong> ' . $remarks . '</div>'
                                        . '</div>'
                                        . '</div>';
                                }

                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull()
                            ->html(),
                    ])
                    ->visible(fn(ForLiquidation $record) => ! empty($this->getReceiptEntries($record))),

                Section::make('Dates')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('cashRequest.created_at')
                            ->label('Date Requested')
                            ->date(),

                        TextEntry::make('cashRequest.forCashRelease.releasing_date')
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

    private function getReceiptEntries(ForLiquidation $record): array
    {
        static $cache = [];

        if (! array_key_exists($record->id, $cache)) {
            $cache[$record->id] = LiquidationReceipt::query()
                ->where('liquidation_id', $record->id)
                ->get()
                ->flatMap(function (LiquidationReceipt $receipt) {
                    return $receipt->getMedia('liquidation-receipts')->map(fn($media) => [
                        'url'     => $media->getUrl(),
                        'amount'  => $receipt->receipt_amount,
                        'remarks' => $receipt->remarks,
                    ]);
                })
                ->filter()
                ->values()
                ->all();
        }

        return $cache[$record->id];
    }
}
