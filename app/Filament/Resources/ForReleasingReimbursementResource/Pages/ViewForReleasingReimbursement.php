<?php

namespace App\Filament\Resources\ForReleasingReimbursementResource\Pages;

use App\Filament\Resources\ForReleasingReimbursementResource;
use App\Filament\Support\RendersAttachmentPreview;
use App\Models\Reimbursement\ReimbursementModeApproval;
use Facades\App\Services\Reimbursement\ForReleasingReimbursementService;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewForReleasingReimbursement extends ViewRecord
{
    use RendersAttachmentPreview;

    protected static string $resource = ForReleasingReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('release')
                ->label('Release')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn($record) => ForReleasingReimbursementService::canRelease($record))
                ->action(fn($record) => ForReleasingReimbursementService::release($record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Reimbursement Details')
                    ->schema([
                        TextEntry::make('reimbursement_no')
                            ->label('Reimbursement No.'),

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
                            ->label('Status Remarks')
                            ->badge(),

                        TextEntry::make('releasing_date')
                            ->label('Releasing Date')
                            ->date(),

                        TextEntry::make('remarks')
                            ->label('Remarks')
                            ->placeholder('-'),
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
            ]);
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
