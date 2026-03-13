<?php

namespace App\Filament\Resources\PaymentProcessResource\Pages;

use App\Enums\CashRequest\DisbursementType;
use App\Enums\CashRequest\NatureOfRequestEnum;
use App\Filament\Resources\PaymentProcessResource;
use App\Filament\Support\RendersAttachmentPreview;
use Carbon\Carbon;
use Facades\App\Services\CashRequest\PaymentProcessService;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Alignment;

class ViewPaymentProcess extends ViewRecord
{
    use RendersAttachmentPreview;

    protected static string $resource = PaymentProcessResource::class;

    /**
     * Define the header actions for setting disbursement, approving, or rejecting.
     *
     * @return array
     */
    protected function getHeaderActions(): array
    {
        return [
            // SET DISBURSEMENT BUTTON
            Action::make('set_disbursement')
                ->label('Set Disbursement')
                ->visible(fn($record) => PaymentProcessService::canSetDisbursement($record))
                ->color('gray')
                ->form($this->getDisbursementTypeFormSchema())
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->action(function ($record, array $data): void {
                    $data['disbursement_type'] ??= $this->resolveDisbursementType($record);

                    PaymentProcessService::saveDisbursementType($record, $data);
                }),

            // OVERRIDE BUTTON
            Action::make('override')
                ->label('Override')
                ->color('warning')
                ->requiresConfirmation()
                ->form(fn($record) => $this->getOverrideFormSchema($record))
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn($record, array $data) => PaymentProcessService::overrideRequest($record, $data))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn($record) => PaymentProcessService::canOverride($record)),

            // APPROVED BUTTON (For Treasury Manager)
            Action::make('approve_request')
                ->label('Approve Request')
                ->color('primary')
                ->requiresConfirmation()
                ->form(fn($record) => $this->getOverrideFormSchema($record))
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn($record, array $data) => PaymentProcessService::approveCashRequest($record, $data))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn($record) => PaymentProcessService::canApproveRequest($record)),

            // APPROVED BUTTON WITH RELEASE FORM (For Treasury Staff)
            Action::make('Approve')
                ->color('primary')
                ->requiresConfirmation()
                ->form(fn($record) => $this->getApproveFormSchema($record))
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn($record, array $data) => PaymentProcessService::approveCashRequestWithReleaseForm($record, $data))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn($record) => PaymentProcessService::canApproveRequestWithRemarks($record)),

            // REJECTION BUTTON
            Action::make('Reject')
                ->color('secondary')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Cash Request')
                ->modalSubmitActionLabel('Reject')
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn($record, array $data) => PaymentProcessService::rejectCashRequest($record, $data))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn($record) => PaymentProcessService::getStatus($record)),
        ];
    }

    /**
     * Build the request detail infolist and activity sections for the view page.
     *
     * @param Infolist $infolist
     * @return Infolist
     */
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Cash Request Details')
                    ->schema([
                        TextEntry::make('request_no')
                            ->label('Request No.')
                            ->copyable(),

                        TextEntry::make('user.name')
                            ->label('Requestor'),

                        TextEntry::make('nature_of_request')
                            ->label('Nature of Request')
                            ->badge(),

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
                            ->badge(),
                    ])
                    ->columns(4),

                Section::make('Proposed Schedule')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('forCashRelease.proposed_releasing_date')
                            ->label('Proposed Releasing Date')
                            ->date()
                            ->placeholder('-'),

                        TextEntry::make('forCashRelease.proposed_releasing_time_from')
                            ->label('Proposed Releasing Time From')
                            ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('h:i A') : '-'),

                        TextEntry::make('forCashRelease.proposed_releasing_time_to')
                            ->label('Proposed Releasing Time To')
                            ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('h:i A') : '-'),

                        TextEntry::make('proposed_due_date')
                            ->label('Proposed Due Date')
                            ->date()
                            ->placeholder('-'),
                    ])
                    ->columns(4)
                    ->visible(fn($record) => $record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value && $record->is_override),

                Section::make('Activity Information')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('activityLists')
                            ->label('')
                            ->getStateUsing(fn($record) => $record->activityLists()
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
                                        ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                                            'wire:loading.attr' => 'disabled',
                                        ]))
                                        ->form([
                                            Textarea::make('rejection_remarks')
                                                ->label('Rejection Remarks')
                                                ->required()
                                                ->maxLength(65535),
                                        ])
                                        ->visible(fn($record): bool => PaymentProcessService::getStatus($this->record) && $record->status !== 'rejected')
                                        ->action(fn(array $data, $record) => PaymentProcessService::rejectActivity($record, $data)),
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

                                TextEntry::make('attachment')
                                    ->label('Attached File/Images')
                                    ->state(fn($record) => $this->renderAttachmentsHtml($record))
                                    ->html()
                                    ->columnSpanFull(),

                                TextEntry::make('status')
                                    ->label('Activity Status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'rejected' => 'danger',
                                        'pending' => 'warning',
                                        default => 'gray',
                                    }),

                                TextEntry::make('rejection_remarks')
                                    ->label('Rejection Remarks')
                                    ->visible(fn($record) => filled($record->rejection_remarks))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),

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

                        TextEntry::make('dv_number')
                            ->label('DV Number')
                            ->visible(fn($record) => $record->isCheckDisbursement() || $record->isPayrollDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('cut_off_date')
                            ->label('Cut-off Date')
                            ->date()
                            ->visible(fn($record) => $record->isPayrollDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('disbursementAddedBy.name')
                            ->label('Added By'),
                    ])
                    ->columns(3)
                    ->visible(fn($record) => $record->disbursement_type != null),
            ]);
    }

    private function getOverrideFormSchema($record): array
    {
        if ($record->nature_of_request !== NatureOfRequestEnum::CASH_ADVANCE->value) {
            return [];
        }

        $agingDays = PaymentProcessService::getAgingDaysFromSettings();

        return [
            DatePicker::make('proposed_releasing_date')
                ->label('Proposed Releasing Date')
                ->required()
                ->live()
                ->default(fn($record) => $record->forCashRelease?->proposed_releasing_date ?? now())
                ->minDate(now()->toDateString())
                ->afterStateUpdated(function ($state, Set $set) use ($agingDays): void {
                    if (!$state) {
                        return;
                    }

                    $set('proposed_due_date', PaymentProcessService::adjustDueDateToWeekday(Carbon::parse($state)->addDays($agingDays)));
                }),

            TimePicker::make('proposed_releasing_time_from')
                ->label('Proposed Releasing Time From')
                ->required()
                ->default(fn($record) => $record->forCashRelease?->proposed_releasing_time_from ?? now()),

            TimePicker::make('proposed_releasing_time_to')
                ->label('Proposed Releasing Time To')
                ->required()
                ->default(fn($record) => $record->forCashRelease?->proposed_releasing_time_to ?? now()),

            DatePicker::make('proposed_due_date')
                ->label('Proposed Due Date')
                ->required()
                ->readonly(fn() => PaymentProcessService::canEditProposedDueDate())
                ->default(function (Get $get, $record) use ($agingDays) {
                    $releasingDate = $get('proposed_releasing_date') ?? $record->forCashRelease?->proposed_releasing_date ?? now();

                    return PaymentProcessService::adjustDueDateToWeekday(Carbon::parse($releasingDate)->addDays($agingDays));
                })
                ->minDate(function (Get $get) use ($agingDays) {
                    $releasingDate = $get('proposed_releasing_date') ?? now()->toDateString();

                    return PaymentProcessService::adjustDueDateToWeekday(Carbon::parse($releasingDate)->addDays($agingDays));
                }),
        ];
    }

    /**
     * Build the disbursement type selection form schema with conditional fields.
     *
     * @return array
     */
    private function getDisbursementTypeFormSchema(): array
    {
        return array_merge(
            [
                Select::make('disbursement_type')
                    ->label('Disbursement Type')
                    ->options(DisbursementType::filamentOptions())
                    ->default(fn($record) => $this->resolveDisbursementType($record))
                    ->required(fn($record) => !$this->isForDepositModeOfTransfer($record))
                    ->dehydrated()
                    ->hidden(fn($record) => $this->isForDepositModeOfTransfer($record))
                    ->live(),

                TextInput::make('amount')
                    ->label('Amount')
                    ->prefix('₱')
                    ->readonly()
                    ->default(fn($record) => number_format($record->requesting_amount, 2)),
            ],
            $this->getCheckDisbursementTypeSchema(),
            $this->getPayrollDisbursementTypeSchema()
        );
    }

    /**
     * Build the check-specific disbursement fields.
     *
     * @return array
     */
    private function getCheckDisbursementTypeSchema(): array
    {
        return [
            TextInput::make('check_branch_name')
                ->label('Check Branch Name')
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),

            TextInput::make('check_no')
                ->label('Check No.')
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),

            TextInput::make('dv_number')
                ->label('DV Number')
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),

            TextInput::make('voucher_no')
                ->label('Voucher No.')
                ->default(fn($record) => $record->voucher_no)
                ->readonly()
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),
        ];
    }

    /**
     * Build the payroll-specific disbursement fields.
     *
     * @return array
     */
    private function getPayrollDisbursementTypeSchema(): array
    {
        return [
            TextInput::make('dv_number')
                ->label('DV Number')
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value),

            DatePicker::make('cut_off_date')
                ->label('Payroll Credit Date')
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value),
        ];
    }

    private function getApproveFormSchema($record): array
    {
        if (
            $record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value
            && $record->disbursement_type === DisbursementType::PAYROLL->value
        ) {
            return $this->getPayrollApproveFormSchema();
        }

        return $this->getStandardApproveFormSchema();
    }

    /**
     * Build the payroll-specific approval form schema.
     *
     * @return array
     */
    private function getPayrollApproveFormSchema(): array
    {
        return [
            Textarea::make('remarks')
                ->required(),

            DatePicker::make('payroll_date')
                ->label('Payroll Date')
                ->required()
                ->default(now())
                ->minDate(now()->toDateString()),
        ];
    }

    /**
     * Build the standard approval form schema for releasing cash requests.
     *
     * @return array
     */
    private function getStandardApproveFormSchema(): array
    {
        return [
            Textarea::make('remarks')
                ->required(),

            DatePicker::make('releasing_date')
                ->label('Releasing Date')
                ->required()
                ->default(now())
                ->minDate(now()->toDateString()),

            TimePicker::make('releasing_time_from')
                ->label('Releasing Time From')
                ->required()
                ->default(now()),

            TimePicker::make('releasing_time_to')
                ->label('Releasing Time To')
                ->required()
                ->default(now()),
        ];
    }

    private function resolveDisbursementType($record): ?string
    {
        if ($this->isForDepositModeOfTransfer($record)) {
            return DisbursementType::PAYROLL->value;
        }

        return $record->disbursement_type;
    }

    private function isForDepositModeOfTransfer($record): bool
    {
        $modeOfTransfer = strtolower(trim(str_replace('_', ' ', (string)($record->mode_of_transfer ?? ''))));

        return $modeOfTransfer === 'for deposit';
    }
}
