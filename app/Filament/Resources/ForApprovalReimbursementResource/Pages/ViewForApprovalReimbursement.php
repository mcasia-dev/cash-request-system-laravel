<?php

namespace App\Filament\Resources\ForApprovalReimbursementResource\Pages;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\ForApprovalReimbursementResource;
use App\Filament\Support\RendersAttachmentPreview;
use App\Filament\Support\RendersDiscussionChat;
use App\Models\Reimbursement\ReimbursementModeApproval;
use Facades\App\Services\Reimbursement\ForApprovalReimbursementService;
use App\Services\Reimbursement\ReimbursementApprovalFlowService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;

class ViewForApprovalReimbursement extends ViewRecord
{
    use RendersAttachmentPreview, RendersDiscussionChat;

    protected static string $resource = ForApprovalReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Approve')
                ->visible(fn($record) => ($record->status === Status::PENDING->value || $record->status === Status::IN_PROGRESS->value) && $this->canCurrentUserReview($record) && $this->canApproveCurrentStep($record))
                ->requiresConfirmation()
                ->form(fn($record) => $this->buildDynamicStepForm($record))
                ->action(fn($record, array $data) => ForApprovalReimbursementService::approve($record, $data)),

            Action::make('Reject')
                ->visible(fn($record) => ($record->status === Status::PENDING->value || $record->status === Status::IN_PROGRESS->value) && $this->canCurrentUserReview($record) && $this->canRejectCurrentStep($record))
                ->color('secondary')
                ->form([
                    ...$this->buildDynamicStepForm($this->record),
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Reimbursement')
                ->modalSubmitActionLabel('Reject')
                ->action(fn($record, array $data) => ForApprovalReimbursementService::reject($record, $data)),

            Action::make('Return')
                ->visible(fn($record) => ($record->status === Status::PENDING->value || $record->status === Status::IN_PROGRESS->value) && $this->canCurrentUserReview($record))
                ->color('warning')
                ->form([
                    Textarea::make('remarks')
                        ->label('Return Remarks')
                        ->required()
                        ->rows(4),
                ])
                ->action(fn($record, array $data) => ForApprovalReimbursementService::returnForClarification($record, $data)),
        ];
    }

    private function canCurrentUserReview($record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return app(ReimbursementApprovalFlowService::class)->userCanReview($record, $user);
    }

    private function canApproveCurrentStep($record): bool
    {
        $config = $this->getCurrentStepConfig($record);

        return (bool) ($config['can_approve'] ?? true);
    }

    private function canRejectCurrentStep($record): bool
    {
        $config = $this->getCurrentStepConfig($record);

        return (bool) ($config['can_reject'] ?? true);
    }

    private function buildDynamicStepForm($record): array
    {
        $config = $this->getCurrentStepConfig($record);
        $schema = (array) ($config['form_schema'] ?? []);
        $fields = [];

        foreach ($schema as $item) {
            $key = (string) ($item['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $label = (string) ($item['label'] ?? $key);
            $type = (string) ($item['type'] ?? 'text');
            $required = (bool) ($item['required'] ?? false);
            $fieldPath = "step_form_data.{$key}";

            $field = match ($type) {
                'number' => TextInput::make($fieldPath)->numeric(),
                'textarea' => Textarea::make($fieldPath)->rows(3),
                'date' => DatePicker::make($fieldPath),
                default => TextInput::make($fieldPath),
            };

            $field->label($label);

            if ($required) {
                $field->required();
            }

            $fields[] = $field;
        }

        return $fields;
    }

    private function getCurrentStepConfig($record): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return ForApprovalReimbursementService::getCurrentStepConfiguration($record, $user) ?? [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Reimbursement Details')
                    ->schema([
                        TextEntry::make('reimbursement_no')
                            ->label('Reimbursement No.'),

                        TextEntry::make('reimbursement_date')
                            ->label('Date')
                            ->date(),

                        TextEntry::make('payee.name')
                            ->label('Payee'),

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
                            ->label('Status Remarks')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                Status::PENDING->value => 'warning',
                                Status::IN_PROGRESS->value => 'secondary',
                                Status::REJECTED->value => 'danger',
                                Status::APPROVED->value => 'success',
                                Status::RELEASED->value => 'info',
                                default => 'secondary',
                            }),

                        TextEntry::make('purpose')
                            ->label('Purpose')
                            ->columnSpanFull(),

                        TextEntry::make('reason_for_rejection')
                            ->label('Reason for Rejection')
                            ->visible(fn($record) => filled($record->reason_for_rejection))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Items to Reimburse')
                    ->schema([
                        RepeatableEntry::make('reimbursementItems')
                            ->label('')
                            ->schema([
                                TextEntry::make('item_name')
                                    ->label('Item'),

                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('PHP'),

                                TextEntry::make('description')
                                    ->label('Description')
                                    ->columnSpanFull(),

                                TextEntry::make('attachment')
                                    ->label('Attachment')
                                    ->state(fn($record) => $this->renderAttachmentsHtml($record))
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),

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

                Section::make('Clarifications / Returns')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('discussion_chat')
                            ->hiddenLabel()
                            ->state(fn($record) => $this->renderDiscussionChatHtml($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private function renderCustomStepFormsHtml($record): HtmlString
    {
        $approvals = $record->reimbursementApprovals()
            ->with('approver')
            ->orderBy('step_no')
            ->orderBy('id')
            ->get()
            ->filter(fn($approval) => is_array($approval->step_form_data) && ! empty($approval->step_form_data));

        if ($approvals->isEmpty()) {
            return new HtmlString('<span style="color:#6b7280;">No custom form values submitted.</span>');
        }

        $html = '<div style="display:flex;flex-direction:column;gap:12px;">';

        foreach ($approvals as $approval) {
            $role = str_replace('_', ' ', (string) $approval->role_name);
            $role = ucwords($role);
            $status = ucfirst((string) $approval->status);
            $approverName = $approval->approver?->name ? e($approval->approver->name) : 'N/A';
            $actedAt = $approval->acted_at ? e($approval->acted_at->format('F d, Y h:i A')) : 'N/A';

            $html .= '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;">';
            $html .= '<div style="font-weight:600;margin-bottom:6px;">Step ' . (int) $approval->step_no . ' - ' . e($role) . '</div>';
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

        if ((int) $approval->step_no === 1 && (string) $approval->role_name === 'department_head') {
            return $defaultLabel;
        }

        $departmentId = $record->payee?->department_id;

        $modeSteps = ReimbursementModeApproval::query()
            ->where('reimbursement_mode_id', $record->reimbursement_mode_id)
            ->orderBy('step_no')
            ->get();

        $normalizedModeSteps = $modeSteps
            ->filter(function ($step) use ($departmentId) {
                return ! (
                    $step->role_name === 'department_head'
                    && (int) $step->department_id === (int) $departmentId
                );
            })
            ->values();

        $index = max(((int) $approval->step_no) - 2, 0);
        $step = $normalizedModeSteps->get($index);
        $schema = is_array($step?->form_schema) ? $step->form_schema : [];

        foreach ($schema as $field) {
            if ((string) ($field['key'] ?? '') === $key) {
                return (string) ($field['label'] ?? $defaultLabel);
            }
        }

        return $defaultLabel;
    }
}
