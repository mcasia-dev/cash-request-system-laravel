<?php
namespace App\Filament\Resources\ForLiquidationResource\Pages;

use App\Enums\CashRequest\DisbursementType;
use App\Enums\CashRequest\Status;
use App\Filament\Resources\ForLiquidationResource;
use App\Models\ForLiquidation;
use Facades\App\Services\CashRequest\ForLiquidationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Alignment;

class ViewForLiquidation extends ViewRecord
{
    protected static string $resource = ForLiquidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // OVERRIDE BUTTON
            Action::make('override')
                ->label('Override')
                ->color('warning')
                ->requiresConfirmation()
                ->form(fn(ForLiquidation $record) => $this->getOverrideFormSchema($record))
                ->action(fn(ForLiquidation $record, array $data) => ForLiquidationService::overrideRequest($record, $data))
                ->visible(fn(ForLiquidation $record) => ForLiquidationService::canOverride($record) && $record->receipt_amount != null),

            // LIQUIDATE BUTTON
            Action::make('liquidate')
                ->label('Liquidate')
                ->color('primary')
                ->requiresConfirmation()
                ->action(fn(ForLiquidation $record) => ForLiquidationService::liquidateRequest($record))
                ->visible(fn(ForLiquidation $record) => ForLiquidationService::canProcess($record) && ForLiquidationService::isTreasuryManager()),

            // REJECT BUTTON
            Action::make('reject')
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
                ->action(fn(ForLiquidation $record, array $data) => ForLiquidationService::rejectLiquidation($record, $data))
                ->visible(fn(ForLiquidation $record) => ForLiquidationService::canProcess($record)),
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

                        TextEntry::make('cashRequest.nature_of_request')
                            ->label('Nature of Request')
                            ->badge(),

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
                    ->schema([
                        RepeatableEntry::make('cashRequest.activityLists')
                            ->label('')
                            ->getStateUsing(fn($record) => $record->cashRequest->activityLists()
                                    ->where('status', '!=', 'rejected')
                                    ->get())
                            ->schema([
                                Actions::make([
                                    InfolistAction::make('rejectActivity')
                                        ->icon('heroicon-o-minus')
                                        ->iconButton()
                                        ->tooltip('Reject activity')
                                        ->color('danger')
                                        ->size('xs')
                                        ->extraAttributes([
                                            'class' => 'border border-red-500 rounded-full text-transparent hover:bg-red-50',
                                        ])
                                        ->modalHeading('Reject Activity')
                                        ->modalDescription('Are you sure you want to reject this activity?')
                                        ->modalSubmitActionLabel('Reject')
                                        ->form([
                                            Textarea::make('rejection_remarks')
                                                ->label('Rejection Remarks')
                                                ->required()
                                                ->maxLength(65535),
                                        ])
                                        ->visible(fn($record): bool => ForLiquidationService::canProcess($this->record) && $record->status !== Status::REJECTED->value)
                                        ->action(fn(array $data, $record) => ForLiquidationService::rejectActivity($record, $data)),
                                ])
                                    ->alignment(Alignment::End)
                                    ->fullWidth()
                                    ->columnSpanFull(),

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

                                SpatieMediaLibraryImageEntry::make('attachment')
                                    ->label('Attached File/Image')
                                    ->collection('attachments')
                                    ->columnSpanFull(),

                                TextEntry::make('status')
                                    ->label('Activity Status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'rejected' => 'danger',
                                        'pending'  => 'warning',
                                        default    => 'gray',
                                    }),

                                TextEntry::make('rejection_remarks')
                                    ->label('Rejection Remarks')
                                    ->visible(fn($record) => filled($record->rejection_remarks))
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

                        TextEntry::make('total_liquidated')
                            ->label('Total Liquidated')
                            ->money('PHP'),

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
                            ->state(ForLiquidationService::getReceiptImageState())
                            ->columnSpanFull()
                            ->html(),
                    ])
                    ->visible(fn(ForLiquidation $record) => ! empty(ForLiquidationService::getReceiptEntries($record))),

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
                            ->visible(fn($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('check_no')
                            ->label('Check No.')
                            ->visible(fn($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('voucher_no')
                            ->label('Voucher No.')
                            ->visible(fn($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('cut_off_date')
                            ->label('Cut-off Date')
                            ->date()
                            ->visible(fn($record) => $record->isPayrollDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('payroll_credit')
                            ->label('Payroll Credit')
                            ->money('PHP')
                            ->visible(fn($record) => $record->isPayrollDisbursement())
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

    private function getOverrideFormSchema(ForLiquidation $record): array
    {
        [$totalReceipts, $requestingAmount, $amountToReturn, $amountToReimburse, $diff] = ForLiquidationService::getLiquidationTotals($record);

        if (abs($diff) < 0.01) {
            return [];
        }

        $amountField = $amountToReturn > 0
            ? TextInput::make('amount_to_return')
                ->label('Amount to Return')
                ->numeric()
                ->required()
                ->default($amountToReturn)
                ->readOnly()
            : TextInput::make('amount_to_reimburse')
                ->label('Amount to Reimburse')
                ->numeric()
                ->required()
                ->default($amountToReimburse)
                ->readOnly();

        return [
            Textarea::make('override_remarks')
                ->label('Override Remarks')
                ->required()
                ->maxLength(65535),
            $amountField,
        ];
    }
}
