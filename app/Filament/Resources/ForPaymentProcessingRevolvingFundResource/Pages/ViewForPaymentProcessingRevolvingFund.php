<?php

namespace App\Filament\Resources\ForPaymentProcessingRevolvingFundResource\Pages;

use App\Enums\CashRequest\DisbursementType;
use App\Filament\Resources\ForPaymentProcessingRevolvingFundResource;
use Facades\App\Services\RevolvingFund\ForPaymentProcessingRevolvingFundService;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewForPaymentProcessingRevolvingFund extends ViewRecord
{
    protected static string $resource = ForPaymentProcessingRevolvingFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('set_disbursement')
                ->label('Set Disbursement')
                ->visible(fn ($record) => ForPaymentProcessingRevolvingFundService::canSetDisbursement($record))
                ->color('gray')
                ->form($this->getDisbursementTypeFormSchema())
                ->modalSubmitAction(fn (StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->action(function ($record, array $data): void {
                    $data['disbursement_type'] ??= $this->resolveDisbursementType($record);
                    ForPaymentProcessingRevolvingFundService::saveDisbursementType($record, $data);
                }),
            Action::make('override')
                ->label('Override')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn ($record) => ForPaymentProcessingRevolvingFundService::overrideRequest($record))
                ->visible(fn ($record) => ForPaymentProcessingRevolvingFundService::canOverride($record)),
            Action::make('approve_request')
                ->label('Approve Request')
                ->color('primary')
                ->requiresConfirmation()
                ->action(fn ($record) => ForPaymentProcessingRevolvingFundService::approveRevolvingFund($record))
                ->visible(fn ($record) => ForPaymentProcessingRevolvingFundService::canApproveRequest($record)),
            Action::make('Approve')
                ->color('primary')
                ->requiresConfirmation()
                ->form(fn ($record) => $this->getApproveFormSchema($record))
                ->modalSubmitAction(fn (StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn ($record, array $data) => ForPaymentProcessingRevolvingFundService::approveWithReleaseForm($record, $data))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn ($record) => ForPaymentProcessingRevolvingFundService::canApproveRequestWithRemarks($record)),
            Action::make('Reject')
                ->color('secondary')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Revolving Fund')
                ->modalSubmitActionLabel('Reject')
                ->modalSubmitAction(fn (StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn ($record, array $data) => ForPaymentProcessingRevolvingFundService::rejectRevolvingFund($record, $data))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn ($record) => ForPaymentProcessingRevolvingFundService::getStatus($record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Revolving Fund Details')
                    ->schema([
                        TextEntry::make('fund_code')->label('Fund Code')->copyable(),
                        TextEntry::make('addedBy.name')->label('Requestor'),
                        TextEntry::make('user.name')->label('Recipient'),
                        TextEntry::make('modeOfTransfer.name')->label('Mode of Transfer')->badge(),
                        TextEntry::make('initial_amount')->label('Initial Amount')->money('PHP'),
                        TextEntry::make('remaining_amount')->label('Remaining Amount')->money('PHP'),
                        TextEntry::make('area_of_assignment')->label('Area of Assignment'),
                        TextEntry::make('purposes.purpose')->label('Purposes')->badge()->columnSpan(2),
                        TextEntry::make('other_purpose')
                            ->label('Other Purpose')
                            ->visible(fn ($record) => filled($record->other_purpose)),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('status_remarks')->badge(),
                        TextEntry::make('remarks')
                            ->label('Remarks')
                            ->placeholder('-'),
                    ])
                    ->columns(4),
                Section::make('Field Work Assignment')
                    ->schema([
                        TextEntry::make('field_work_assignment')
                            ->hiddenLabel()
                            ->state(function ($record) {
                                return collect($record->field_work_assignment ?? [])
                                    ->map(function ($item) {
                                        $day = ucfirst((string) ($item['day'] ?? ''));
                                        $from = $item['time_from'] ?? '-';
                                        $to = $item['time_to'] ?? '-';

                                        return "{$day}: {$from} - {$to}";
                                    })
                                    ->join('<br>');
                            })
                            ->html(),
                    ]),
                Section::make('Disbursement Method')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('disbursement_type')
                            ->label('Disbursement Type')
                            ->badge()
                            ->placeholder('Not yet set'),
                        TextEntry::make('initial_amount')
                            ->label('Amount')
                            ->money('PHP'),
                        TextEntry::make('check_branch_name')
                            ->label('Check Branch Name')
                            ->visible(fn ($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),
                        TextEntry::make('check_no')
                            ->label('Check No.')
                            ->visible(fn ($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),
                        TextEntry::make('dv_number')
                            ->label('DV Number')
                            ->visible(fn ($record) => $record->isCheckDisbursement() || $record->isPayrollDisbursement())
                            ->placeholder('-'),
                        TextEntry::make('voucher_no')
                            ->label('Voucher No.')
                            ->visible(fn ($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),
                        TextEntry::make('cut_off_date')
                            ->label('Payroll Credit Date')
                            ->date()
                            ->visible(fn ($record) => $record->isPayrollDisbursement())
                            ->placeholder('-'),
                        TextEntry::make('disbursementAddedBy.name')
                            ->label('Added By'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->disbursement_type !== null),
                Section::make('Approval Form Details')
                    ->visible(fn ($record) => $record->revolvingFundApprovals()
                        ->whereNotNull('step_form_data')
                        ->exists())
                    ->schema([
                        TextEntry::make('custom_step_forms')
                            ->hiddenLabel()
                            ->state(fn ($record) => $this->renderCustomStepFormsHtml($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private function getDisbursementTypeFormSchema(): array
    {
        return array_merge(
            [
                Select::make('disbursement_type')
                    ->label('Disbursement Type')
                    ->options(DisbursementType::filamentOptions())
                    ->default(fn ($record) => $this->resolveDisbursementType($record))
                    ->required(fn ($record) => ! $this->isForDepositModeOfTransfer($record))
                    ->dehydrated()
                    ->hidden(fn ($record) => $this->isForDepositModeOfTransfer($record))
                    ->live(),
                TextInput::make('amount')
                    ->label('Amount')
                    ->prefix('PHP')
                    ->readonly()
                    ->default(fn ($record) => number_format((float) $record->initial_amount, 2)),
            ],
            $this->getCheckDisbursementTypeSchema(),
            $this->getPayrollDisbursementTypeSchema(),
        );
    }

    private function getCheckDisbursementTypeSchema(): array
    {
        return [
            TextInput::make('check_branch_name')
                ->label('Check Branch Name')
                ->visible(fn (Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn (Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),
            TextInput::make('check_no')
                ->label('Check No.')
                ->visible(fn (Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn (Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),
            TextInput::make('dv_number')
                ->label('DV Number')
                ->visible(fn (Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn (Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),
            TextInput::make('voucher_no')
                ->label('Voucher No.')
                ->visible(fn (Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn (Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),
        ];
    }

    private function getPayrollDisbursementTypeSchema(): array
    {
        return [
            TextInput::make('dv_number')
                ->label('DV Number')
                ->visible(fn (Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value)
                ->required(fn (Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value),
            DatePicker::make('cut_off_date')
                ->label('Payroll Credit Date')
                ->visible(fn (Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value)
                ->required(fn (Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value),
        ];
    }

    private function getApproveFormSchema($record): array
    {
        if ($record->disbursement_type === DisbursementType::PAYROLL->value) {
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
        $modeOfTransfer = strtolower(trim((string) ($record->modeOfTransfer?->name ?? '')));

        return $modeOfTransfer === 'for deposit';
    }

    private function renderCustomStepFormsHtml($record): HtmlString
    {
        $approvals = $record->revolvingFundApprovals()
            ->with('approver')
            ->orderBy('step_order')
            ->orderBy('id')
            ->get()
            ->filter(fn ($approval) => is_array($approval->step_form_data) && ! empty($approval->step_form_data));

        if ($approvals->isEmpty()) {
            return new HtmlString('<span style="color:#6b7280;">No custom form values submitted.</span>');
        }

        $html = '<div style="display:flex;flex-direction:column;gap:12px;">';

        foreach ($approvals as $approval) {
            $role = ucwords(str_replace('_', ' ', (string) $approval->role_name));
            $status = ucfirst((string) $approval->status);
            $approverName = $approval->approver?->name ? e($approval->approver->name) : 'N/A';
            $actedAt = $approval->acted_at ? e($approval->acted_at->format('F d, Y h:i A')) : 'N/A';

            $html .= '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;">';
            $html .= '<div style="font-weight:600;margin-bottom:6px;">Step ' . (int) $approval->step_order . ' - ' . e($role) . '</div>';
            $html .= '<div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Status: ' . e($status) . ' | By: ' . $approverName . ' | At: ' . $actedAt . '</div>';
            $html .= '<ul style="margin:0;padding-left:18px;">';

            foreach ($approval->step_form_data as $key => $value) {
                $label = $this->resolveApprovalFieldLabel($record, $approval, (string) $key);
                $displayValue = blank($value) ? '-' : (is_scalar($value) ? (string) $value : json_encode($value));
                $html .= '<li><strong>' . e($label) . ':</strong> ' . e($displayValue) . '</li>';
            }

            $html .= '</ul></div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }

    private function resolveApprovalFieldLabel($record, $approval, string $key): string
    {
        $defaultLabel = ucwords(str_replace('_', ' ', $key));

        if ((int) $approval->step_order === 1 && (string) $approval->role_name === 'department_head') {
            return $defaultLabel;
        }

        $steps = $record->revolvingFundApprovals()
            ->orderBy('step_order')
            ->orderBy('id')
            ->get(['role_name', 'step_order']);

        $autoPrependedDepartmentHead = $steps->first()?->role_name === 'department_head';
        $targetStepOrder = $autoPrependedDepartmentHead ? ((int) $approval->step_order - 1) : (int) $approval->step_order;

        $ruleStep = app(\App\Services\RevolvingFund\RevolvingFundApprovalFlowService::class)->resolveRule($record)?->steps()
            ->where('step_order', $targetStepOrder)
            ->orderBy('id')
            ->first();

        $schema = is_array($ruleStep?->form_schema) ? $ruleStep->form_schema : [];

        foreach ($schema as $field) {
            if ((string) ($field['key'] ?? '') === $key) {
                return (string) ($field['label'] ?? $defaultLabel);
            }
        }

        return $defaultLabel;
    }
}
