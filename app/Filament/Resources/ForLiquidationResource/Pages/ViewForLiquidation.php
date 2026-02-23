<?php
namespace App\Filament\Resources\ForLiquidationResource\Pages;

use App\Enums\CashRequest\DisbursementType;
use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\ForLiquidationResource;
use App\Models\ForLiquidation;
use App\Models\LiquidationReceipt;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ViewForLiquidation extends ViewRecord
{
    protected static string $resource = ForLiquidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Liquidate')
                ->label('Liquidate')
                ->color('primary')
                ->requiresConfirmation()
                ->action(fn(ForLiquidation $record) => $this->liquidateRequest($record))
                ->visible(fn(ForLiquidation $record) => $this->canProcess($record)),

            Action::make('Reject')
                ->label('Reject')
                ->color('secondary')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('rejection_remarks')
                        ->label('Rejection Remarks')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Liquidation')
                ->modalSubmitActionLabel('Reject')
                ->action(fn(ForLiquidation $record, array $data) => $this->rejectLiquidation($record, $data))
                ->visible(fn(ForLiquidation $record) => $this->canProcess($record)),
        ];
    }

    /**
     * Build the liquidation view infolist with request, payment, and receipt details.
     */
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
                            ->state($this->getReceiptImageState())
                            ->columnSpanFull()
                            ->html(),
                    ])
                    ->visible(fn(ForLiquidation $record) => ! empty($this->getReceiptEntries($record))),

                Section::make('Disbursement Method')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('disbursement_type')
                            ->label('Disbursement Type')
                            ->badge()
                            ->placeholder('Not yet set'),

                        TextEntry::make('requesting_amount')
                            ->label('Amount')
                            ->money('PHP'),

                        TextEntry::make('check_branch_name')
                            ->label('Check Branch Name')
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::CHECK->value)
                            ->placeholder('-'),

                        TextEntry::make('check_no')
                            ->label('Check No.')
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::CHECK->value)
                            ->placeholder('-'),

                        TextEntry::make('voucher_no')
                            ->label('Voucher No.')
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::CHECK->value)
                            ->placeholder('-'),

                        TextEntry::make('cut_off_date')
                            ->label('Cut-off Date')
                            ->date()
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::PAYROLL->value)
                            ->placeholder('-'),

                        TextEntry::make('payroll_credit')
                            ->label('Payroll Credit')
                            ->money('PHP')
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::PAYROLL->value)
                            ->placeholder('-'),

                        TextEntry::make('disbursementAddedBy.name')
                            ->label('Added By'),
                    ])
                    ->columns(3)
                    ->visible(fn($record) => $record->disbursement_type != null),

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
                            ->label('Liquidation Due Date')
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
     * Load receipt media entries for the liquidation, with per-record caching.
     */
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

    /**
     * Build a closure that renders receipt images and details as HTML.
     * @return \Closure
     */
    public function getReceiptImageState(): \Closure
    {
        return function (ForLiquidation $record) {
            $receipts = $this->getReceiptEntries($record);

            if (empty($receipts)) {
                return 'No receipt images uploaded.';
            }

            $html = '<div style="display:flex;flex-wrap:wrap;gap:10px;">';

            foreach ($receipts as $receipt) {
                $safeUrl = e($receipt['url']);
                $amount = number_format((float)($receipt['amount'] ?? 0), 2);
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
        };
    }

    private function canProcess(ForLiquidation $record): bool
    {
        return $record->cashRequest->status === Status::RELEASED->value
            && $record->cashRequest->status_remarks === StatusRemarks::LIQUIDATION_RECEIPT_SUBMITTED->value;
    }

    private function liquidateRequest(ForLiquidation $record): void
    {
        $user = Auth::user();

        $record->cashRequest->update([
            'status' => Status::LIQUIDATED->value,
            'status_remarks' => StatusRemarks::LIQUIDATED->value,
            'date_liquidated' => Carbon::now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record->cashRequest ?? $record)
            ->event('liquidated')
            ->withProperties([
                'request_no' => $record->cashRequest->request_no,
                'activity_name' => $record->cashRequest->activity_name,
                'requesting_amount' => $record->cashRequest->requesting_amount,
                'previous_status' => Status::RELEASED->value,
                'new_status' => Status::LIQUIDATED->value,
                'status_remarks' => StatusRemarks::LIQUIDATED->value,
            ])
            ->log("Cash request {$record->cashRequest->request_no} was liquidated by {$user->name} ({$user->position})");

        Notification::make()
            ->title('Liquidation approved.')
            ->success()
            ->send();
    }

    private function rejectLiquidation(ForLiquidation $record, array $data): void
    {
        $user = Auth::user();

        $record->update([
            'remarks' => $data['rejection_remarks'],
        ]);

        $record->cashRequest->update([
            'status' => Status::RELEASED->value,
            'status_remarks' => StatusRemarks::FOR_LIQUIDATION->value,
            'reason_for_rejection' => $data['rejection_remarks'],
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record->cashRequest ?? $record)
            ->event('rejected')
            ->withProperties([
                'request_no' => $record->cashRequest->request_no,
                'activity_name' => $record->cashRequest->activity_name,
                'requesting_amount' => $record->cashRequest->requesting_amount,
                'previous_status' => Status::RELEASED->value,
                'new_status' => Status::RELEASED->value,
                'status_remarks' => StatusRemarks::FOR_LIQUIDATION->value,
                'reason_for_rejection' => $data['rejection_remarks'],
            ])
            ->log("Liquidation receipts for cash request {$record->cashRequest->request_no} were rejected by {$user->name} ({$user->position})");

        Notification::make()
            ->title('Liquidation rejected.')
            ->success()
            ->send();
    }
}
