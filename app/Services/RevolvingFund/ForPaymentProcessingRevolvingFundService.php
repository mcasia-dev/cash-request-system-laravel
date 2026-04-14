<?php

namespace App\Services\RevolvingFund;

use App\Enums\CashRequest\DisbursementType;
use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ForPaymentProcessingRevolvingFundService
{
    public function getStatus($record): bool
    {
        return $record->status === Status::IN_PROGRESS->value
            && $record->status_remarks === StatusRemarks::FOR_PAYMENT_PROCESSING->value;
    }

    public function canSetDisbursement($record): bool
    {
        return $this->getStatus($record)
            && $this->isTreasuryStaff()
            && $record->disbursement_added_by === null;
    }

    public function canOverride($record): bool
    {
        return $this->getStatus($record)
            && $this->isTreasuryStaff()
            && !$record->is_override
            && $record->disbursement_type !== null;
    }

    public function canApproveRequest($record): bool
    {
        return $this->getStatus($record)
            && $this->isTreasuryManager()
            && $record->is_override
            && !$record->is_approved_by_treasury_manager;
    }

    public function canApproveRequestWithRemarks($record): bool
    {
        return $this->getStatus($record)
            && $this->isTreasuryStaff()
            && $record->is_override
            && $record->is_approved_by_treasury_manager;
    }

    public function saveDisbursementType($record, array $data): void
    {
        $payload = [
            'disbursement_type' => $data['disbursement_type'],
            'disbursement_added_by' => Auth::id(),
            'check_branch_name' => null,
            'check_no' => null,
            'dv_number' => null,
            'voucher_no' => null,
            'cut_off_date' => null,
        ];

        if ($data['disbursement_type'] === DisbursementType::CHECK->value) {
            $payload['check_branch_name'] = $data['check_branch_name'] ?? null;
            $payload['check_no'] = $data['check_no'] ?? null;
            $payload['dv_number'] = $data['dv_number'] ?? null;
            $payload['voucher_no'] = $data['voucher_no'] ?? null;
        }

        if ($data['disbursement_type'] === DisbursementType::PAYROLL->value) {
            $payload['dv_number'] = $data['dv_number'] ?? null;
            $payload['cut_off_date'] = $data['cut_off_date'] ?? null;
        }

        $record->update($payload);

        Notification::make()
            ->title('Disbursement details saved.')
            ->success()
            ->send();
    }

    public function overrideRequest($record): void
    {
        $user = Auth::user();

        $record->update([
            'is_override' => true,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('override')
            ->withProperties([
                'fund_code' => $record->fund_code,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Revolving fund {$record->fund_code} was overridden by {$user->name} ({$user->position})");

        $managers = User::query()
            ->role('treasury_manager')
            ->get();

        if ($managers->isNotEmpty()) {
            Notification::make()
                ->title('Revolving Fund Overridden')
                ->body("{$record->fund_code} has been overridden and is waiting for manager approval.")
                ->actions([
                    NotificationAction::make('markAsRead')->button()->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.for-payment-processing-revolving-funds.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($managers);
        }

        Notification::make()
            ->title('Override completed')
            ->success()
            ->send();
    }

    public function approveRevolvingFund($record): void
    {
        $user = Auth::user();

        $record->update([
            'is_approved_by_treasury_manager' => true,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'fund_code' => $record->fund_code,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Revolving fund {$record->fund_code} was approved by treasury manager {$user->name} ({$user->position})");

        $staff = User::query()
            ->role('treasury_staff')
            ->get();

        if ($staff->isNotEmpty()) {
            Notification::make()
                ->title('Revolving Fund Approved by Manager')
                ->body("{$record->fund_code} is now ready for release form approval.")
                ->actions([
                    NotificationAction::make('markAsRead')->button()->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.for-payment-processing-revolving-funds.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($staff);
        }

        Notification::make()
            ->title('Manager approval completed')
            ->success()
            ->send();
    }

    public function approveWithReleaseForm($record, array $data): void
    {
        $user = Auth::user();

        $record->update([
            'status' => Status::APPROVED->value,
            'status_remarks' => StatusRemarks::FOR_RELEASING->value,
            'remarks' => $data['remarks'] ?? null,
            'releasing_date' => $data['releasing_date'] ?? $data['payroll_date'] ?? null,
            'releasing_time_from' => $data['releasing_time_from'] ?? null,
            'releasing_time_to' => $data['releasing_time_to'] ?? null,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'fund_code' => $record->fund_code,
                'new_status' => Status::APPROVED->value,
                'status_remarks' => StatusRemarks::FOR_RELEASING->value,
            ])
            ->log("Revolving fund {$record->fund_code} was approved for releasing by {$user->name} ({$user->position})");

        if ($record->addedBy) {
            Notification::make()
                ->title('Revolving Fund Update')
                ->body("Your revolving fund {$record->fund_code} has been approved for releasing.")
                ->actions([
                    NotificationAction::make('markAsRead')->button()->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.revolving-funds.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->addedBy);
        }

        Notification::make()
            ->title('Revolving fund approved')
            ->success()
            ->send();
    }

    public function rejectRevolvingFund($record, array $data): void
    {
        $user = Auth::user();

        $record->update([
            'status' => Status::REJECTED->value,
            'status_remarks' => StatusRemarks::TREASURY_REJECTED->value,
            'remarks' => $data['rejection_reason'],
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('rejected')
            ->withProperties([
                'fund_code' => $record->fund_code,
                'new_status' => Status::REJECTED->value,
                'status_remarks' => StatusRemarks::TREASURY_REJECTED->value,
                'reason_for_rejection' => $data['rejection_reason'],
            ])
            ->log("Revolving fund {$record->fund_code} was rejected by {$user->name} ({$user->position})");

        if ($record->addedBy) {
            Notification::make()
                ->title('Revolving Fund Update')
                ->body("Your revolving fund {$record->fund_code} has been rejected.")
                ->actions([
                    NotificationAction::make('markAsRead')->button()->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.revolving-funds.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->addedBy);
        }

        Notification::make()
            ->title('Revolving fund rejected')
            ->success()
            ->send();
    }

    public function notifyTreasuryForPaymentProcessing($record): void
    {
        $treasuryStaff = User::query()
            ->role('treasury_staff')
            ->get();

        if ($treasuryStaff->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Revolving Fund For Payment Processing')
            ->body("{$record->fund_code} is ready for payment processing.")
            ->actions([
                NotificationAction::make('markAsRead')->button()->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.for-payment-processing-revolving-funds.view', ['record' => $record->id])),
            ])
            ->sendToDatabase($treasuryStaff);
    }

    public function isTreasuryManager(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles()->where('name', 'treasury_manager')->exists();
    }

    public function isTreasuryStaff(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles()->where('name', 'treasury_staff')->exists();
    }

    public function resolveDisbursementType($record): ?string
    {
        if ($this->isForDepositModeOfTransfer($record)) {
            return DisbursementType::PAYROLL->value;
        }

        return $record->disbursement_type;
    }

    public function isForDepositModeOfTransfer($record): bool
    {
        $modeOfTransfer = strtolower(trim((string)($record->modeOfTransfer?->name ?? '')));

        return $modeOfTransfer === 'for deposit';
    }


    public function renderCustomStepFormsHtml($record): HtmlString
    {
        $approvals = $record->revolvingFundApprovals()
            ->with('approver')
            ->orderBy('step_order')
            ->orderBy('id')
            ->get()
            ->filter(fn($approval) => is_array($approval->step_form_data) && !empty($approval->step_form_data));

        if ($approvals->isEmpty()) {
            return new HtmlString('<span style="color:#6b7280;">No custom form values submitted.</span>');
        }

        $html = '<div style="display:flex;flex-direction:column;gap:12px;">';

        foreach ($approvals as $approval) {
            $role = ucwords(str_replace('_', ' ', (string)$approval->role_name));
            $status = ucfirst((string)$approval->status);
            $approverName = $approval->approver?->name ? e($approval->approver->name) : 'N/A';
            $actedAt = $approval->acted_at ? e($approval->acted_at->format('F d, Y h:i A')) : 'N/A';

            $html .= '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;">';
            $html .= '<div style="font-weight:600;margin-bottom:6px;">Step ' . (int)$approval->step_order . ' - ' . e($role) . '</div>';
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

        if ((int)$approval->step_order === 1 && (string)$approval->role_name === 'department_head') {
            return $defaultLabel;
        }

        $steps = $record->revolvingFundApprovals()
            ->orderBy('step_order')
            ->orderBy('id')
            ->get(['role_name', 'step_order']);

        $autoPrependedDepartmentHead = $steps->first()?->role_name === 'department_head';
        $targetStepOrder = $autoPrependedDepartmentHead ? ((int)$approval->step_order - 1) : (int)$approval->step_order;

        $ruleStep = app(\App\Services\RevolvingFund\RevolvingFundApprovalFlowService::class)->resolveRule($record)?->steps()
            ->where('step_order', $targetStepOrder)
            ->orderBy('id')
            ->first();

        $schema = is_array($ruleStep?->form_schema) ? $ruleStep->form_schema : [];

        foreach ($schema as $field) {
            if ((string)($field['key'] ?? '') === $key) {
                return (string)($field['label'] ?? $defaultLabel);
            }
        }

        return $defaultLabel;
    }


    /**
     * @param $record
     * @return string
     */
    public function getFieldWorkAssignmentState($record)
    {
        return collect($record->field_work_assignment ?? [])
            ->map(function ($item) {
                $day = ucfirst((string)($item['day'] ?? ''));
                $from = $item['time_from'] ?? '-';
                $to = $item['time_to'] ?? '-';

                return "{$day}: {$from} - {$to}";
            })
            ->join('<br>');
    }
}
