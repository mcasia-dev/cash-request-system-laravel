<?php

namespace App\Filament\Resources\ForPaymentProcessingReimbursementResource\Pages;

use App\Enums\CashRequest\DisbursementType;
use App\Filament\Resources\ForPaymentProcessingReimbursementResource;
use App\Filament\Support\RendersAttachmentPreview;
use App\Models\Reimbursement\ReimbursementModeApproval;
use Facades\App\Services\Reimbursement\ForPaymentProcessingReimbursementService;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Get;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewForPaymentProcessingReimbursement extends ViewRecord
{
    use RendersAttachmentPreview;

    protected static string $resource = ForPaymentProcessingReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('set_disbursement')
                ->label('Set Disbursement')
                ->visible(fn($record) => ForPaymentProcessingReimbursementService::canSetDisbursement($record))
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
                    ForPaymentProcessingReimbursementService::saveDisbursementType($record, $data);
                }),

            Action::make('override')
                ->label('Override')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn($record) => ForPaymentProcessingReimbursementService::overrideRequest($record))
                ->visible(fn($record) => ForPaymentProcessingReimbursementService::canOverride($record)),

            Action::make('approve_request')
                ->label('Approve Request')
                ->color('primary')
                ->requiresConfirmation()
                ->action(fn($record) => ForPaymentProcessingReimbursementService::approveReimbursement($record))
                ->visible(fn($record) => ForPaymentProcessingReimbursementService::canApproveRequest($record)),

            Action::make('Approve')
                ->color('primary')
                ->requiresConfirmation()
                ->form(fn($record) => $this->getApproveFormSchema($record))
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn($record, array $data) => ForPaymentProcessingReimbursementService::approveWithReleaseForm($record, $data))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn($record) => ForPaymentProcessingReimbursementService::canApproveRequestWithRemarks($record)),

            Action::make('Reject')
                ->color('secondary')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Reimbursement')
                ->modalSubmitActionLabel('Reject')
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn($record, array $data) => ForPaymentProcessingReimbursementService::rejectReimbursement($record, $data))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn($record) => ForPaymentProcessingReimbursementService::getStatus($record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Reimbursement Details')
                    ->schema([
                        TextEntry::make('reimbursement_no')
                            ->label('Reimbursement No.')
                            ->copyable(),
                        TextEntry::make('payee.name')
                            ->label('Requestor'),
                        TextEntry::make('reimbursementMode.name')
                            ->label('Mode of Request')
                            ->badge(),
                        TextEntry::make('mode_of_transfer')
                            ->label('Mode of Transfer')
                            ->badge(),
                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('PHP'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('status_remarks')
                            ->badge(),
                        TextEntry::make('reason_for_rejection')
                            ->label('Reason for Rejection')
                            ->visible(fn($record) => filled($record->reason_for_rejection))
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Section::make('Items to Reimburse')
                    ->schema([
                        RepeatableEntry::make('reimbursementItems')
                            ->label('')
                            ->schema([
                                TextEntry::make('item_name')->label('Item'),
                                TextEntry::make('amount')->label('Amount')->money('PHP'),
                                TextEntry::make('description')->label('Description')->columnSpanFull(),
                                TextEntry::make('attachment')
                                    ->label('Attachment')
                                    ->state(fn($record) => $this->renderAttachmentsHtml($record))
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Disbursement Method')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('disbursement_type')
                            ->label('Disbursement Type')
                            ->badge()
                            ->placeholder('Not yet set'),
                        TextEntry::make('total_amount')
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
                        TextEntry::make('dv_number')
                            ->label('DV Number')
                            ->visible(fn($record) => $record->isCheckDisbursement() || $record->isPayrollDisbursement())
                            ->placeholder('-'),
                        TextEntry::make('voucher_no')
                            ->label('Voucher No.')
                            ->visible(fn($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),
                        TextEntry::make('cut_off_date')
                            ->label('Payroll Credit Date')
                            ->date()
                            ->visible(fn($record) => $record->isPayrollDisbursement())
                            ->placeholder('-'),
                        TextEntry::make('disbursementAddedBy.name')
                            ->label('Added By'),
                    ])
                    ->columns(3)
                    ->visible(fn($record) => $record->disbursement_type != null),

                Section::make('Approval Form Details')
                    ->visible(fn($record) => $record->reimbursementApprovals()
                        ->whereNotNull('step_form_data')
                        ->exists())
                    ->schema([
                        TextEntry::make('custom_step_forms')
                            ->hiddenLabel()
                            ->state(fn($record) => $this->renderCustomStepFormsHtml($record))
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
                    ->default(fn($record) => $this->resolveDisbursementType($record))
                    ->required(fn($record) => !$this->isForDepositModeOfTransfer($record))
                    ->dehydrated()
                    ->hidden(fn($record) => $this->isForDepositModeOfTransfer($record))
                    ->live(),

                TextInput::make('amount')
                    ->label('Amount')
                    ->prefix('PHP')
                    ->readonly()
                    ->default(fn($record) => number_format((float)$record->total_amount, 2)),
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
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),
        ];
    }

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
        $modeOfTransfer = strtolower(trim(str_replace('_', ' ', (string)($record->mode_of_transfer ?? ''))));

        return $modeOfTransfer === 'for deposit';
    }

    private function renderCustomStepFormsHtml($record): HtmlString
    {
        $approvals = $record->reimbursementApprovals()
            ->with('approver')
            ->orderBy('step_no')
            ->orderBy('id')
            ->get()
            ->filter(fn($approval) => is_array($approval->step_form_data) && !empty($approval->step_form_data));

        if ($approvals->isEmpty()) {
            return new HtmlString('<span style="color:#6b7280;">No custom form values submitted.</span>');
        }

        $html = '<div style="display:flex;flex-direction:column;gap:12px;">';

        foreach ($approvals as $approval) {
            $role = str_replace('_', ' ', (string)$approval->role_name);
            $role = ucwords($role);
            $status = ucfirst((string)$approval->status);
            $approverName = $approval->approver?->name ? e($approval->approver->name) : 'N/A';
            $actedAt = $approval->acted_at ? e($approval->acted_at->format('F d, Y h:i A')) : 'N/A';

            $html .= '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;">';
            $html .= '<div style="font-weight:600;margin-bottom:6px;">Step ' . (int)$approval->step_no . ' - ' . e($role) . '</div>';
            $html .= '<div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Status: ' . e($status) . ' | By: ' . $approverName . ' | At: ' . $actedAt . '</div>';
            $html .= '<ul style="margin:0;padding-left:18px;">';

            foreach ($approval->step_form_data as $key => $value) {
                $label = $this->resolveApprovalFieldLabel($record, $approval, (string)$key);
                $displayValue = blank($value) ? '-' : (is_scalar($value) ? (string)$value : json_encode($value));

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

        if ((int)$approval->step_no === 1 && (string)$approval->role_name === 'department_head') {
            return $defaultLabel;
        }

        $departmentId = $record->payee?->department_id;

        $modeSteps = ReimbursementModeApproval::query()
            ->where('reimbursement_mode_id', $record->reimbursement_mode_id)
            ->orderBy('step_no')
            ->get();

        $normalizedModeSteps = $modeSteps
            ->filter(function ($step) use ($departmentId) {
                return !(
                    $step->role_name === 'department_head'
                    && (int)$step->department_id === (int)$departmentId
                );
            })
            ->values();

        $index = max(((int)$approval->step_no) - 2, 0);
        $step = $normalizedModeSteps->get($index);
        $schema = is_array($step?->form_schema) ? $step->form_schema : [];

        foreach ($schema as $field) {
            if ((string)($field['key'] ?? '') === $key) {
                return (string)($field['label'] ?? $defaultLabel);
            }
        }

        return $defaultLabel;
    }
}
