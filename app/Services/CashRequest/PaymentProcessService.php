<?php

namespace App\Services\CashRequest;

use App\Enums\CashRequest\DisbursementType;
use App\Enums\CashRequest\NatureOfRequestEnum;
use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Jobs\CashRequest\ApproveCashRequestByTreasuryJob;
use App\Jobs\CashRequest\RejectCashRequestJob;
use App\Models\CashRequest\ForCashRelease;
use App\Models\User;
use App\Services\Remarks\StatusRemarkResolver;
use App\Traits\AdjustDueDateToBusinessDayTrait;
use App\Traits\GenerateSettingTrait;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentProcessService
{
    use AdjustDueDateToBusinessDayTrait;
    use GenerateSettingTrait;

    /**
     * Determine if the cash request is eligible for payment processing actions.
     *
     * @param mixed $record
     * @return bool
     */
    public function getStatus(mixed $record): bool
    {
        return $record->status === Status::IN_PROGRESS->value && $record->status_remarks === StatusRemarks::FOR_PAYMENT_PROCESSING->value;
    }

    public function canApproveRequest(mixed $record)
    {
        if (!($this->getStatus($record) && $this->isTreasuryManager() && $record->is_override && !$record->is_approved_by_treasury_manager)) {
            return false;
        }

        return true;
    }

    public function canApproveRequestWithRemarks(mixed $record): bool
    {
        if (!($this->getStatus($record) && $this->isTreasuryStaff() && $record->is_override && $record->is_approved_by_treasury_manager)) {
            return false;
        }

        if ($record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value) {
            return $record->disbursement_type !== null;
        }

        return true;
    }

    private function approveCashAdvanceRequest(mixed $record, array $data = [])
    {
        $user = Auth::user();
        $status_remarks = app(StatusRemarkResolver::class)->approveByPermissions($user, 'treasury');

        if ($record->is_override) {
            $proposed_due_date = Carbon::parse($data['proposed_due_date']);
            $new_proposed_date = $this->adjustDueDateToWeekday($proposed_due_date);

            $record->update([
                'proposed_due_date' => $new_proposed_date ?? $record->proposed_due_date,
            ]);

            ForCashRelease::updateOrCreate(
                ['cash_request_id' => $record->id],
                [
                    'proposed_releasing_date' => $data['proposed_releasing_date'] ?? $record->forCashRelease?->proposed_releasing_date,
                    'proposed_releasing_time_from' => $data['proposed_releasing_time_from'] ?? $record->forCashRelease?->proposed_releasing_time_from,
                    'proposed_releasing_time_to' => $data['proposed_releasing_time_to'] ?? $record->forCashRelease?->proposed_releasing_time_to,
                ]
            );

            $record->refresh();
        }

        ForCashRelease::updateOrCreate(['cash_request_id' => $record->id], [
            'cash_request_id' => $record->id,
            'processed_by' => $user->id,
            'releasing_date' => $record->forCashRelease?->proposed_releasing_date ?? null,
            'releasing_time_from' => $record->forCashRelease?->proposed_releasing_time_from ?? null,
            'releasing_time_to' => $record->forCashRelease?->proposed_releasing_time_to ?? null,
            'date_processed' => Carbon::now(),
        ]);

        $record->update([
            'due_date' => $record->proposed_due_date ?? null,
            'status' => Status::APPROVED->value,
            'status_remarks' => $status_remarks,
            'is_approved_by_treasury_manager' => true,
        ]);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => $record->status,
                'new_status' => $record->status,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Cash request {$record->request_no} was approved by {$user->name} ({$user->position})");

        ApproveCashRequestByTreasuryJob::dispatch($record);

        Notification::make()
            ->title('Cash Request Update')
            ->body("Your cash request {$record->request_no} has been approved for releasing.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.cash-requests.track-status', ['record' => $record->id])),
            ])
            ->sendToDatabase($record->user);

        return redirect()->route('filament.admin.resources.payment-processing.index');
    }

    private function approvePettyCashRequest(mixed $record)
    {
        $user = Auth::user();

        $record->update([
            'is_approved_by_treasury_manager' => true,
        ]);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => $record->status,
                'new_status' => $record->status,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Cash request {$record->request_no} was approved by {$user->name} ({$user->position})");

        self::notifyTreasuryStaff(
            $record,
            'Cash Request Update',
            "Cash request {$record->request_no} has been approved by Treasury Manager."
        );

        Notification::make()
            ->title('Cash Request Approved!')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.payment-processing.index');
    }

    public function approveCashRequest($record, array $data = [])
    {
        if ($record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value) {
            return $this->approveCashAdvanceRequest($record, $data);
        }

        return $this->approvePettyCashRequest($record);
    }

    /**
     * Approve the cash request, create the release record, set due date (if applicable),
     * log activity, and dispatch the approval notification.
     *
     * @param mixed $record
     * @param array<string, mixed> $data
     */
    public function approveCashRequestWithReleaseForm($record, array $data)
    {
        $user = Auth::user();
        $status_remarks = app(StatusRemarkResolver::class)->approveByPermissions($user, 'treasury');
        $releasingDate = $data['releasing_date'] ?? $data['payroll_date'] ?? null;
        $timeFrom = $data['releasing_time_from'] ?? null;
        $timeTo = $data['releasing_time_to'] ?? null;

        ForCashRelease::updateOrCreate(['cash_request_id' => $record->id], [
            'cash_request_id' => $record->id,
            'processed_by' => $user->id,
            'remarks' => $data['remarks'],
            'releasing_date' => $releasingDate,
            'releasing_time_from' => $timeFrom,
            'releasing_time_to' => $timeTo,
            'date_processed' => Carbon::now(),
        ]);

        $agingDays = $this->getAgingDaysFromSettings();
        $due_date = self::adjustDueDateToWeekday(Carbon::parse($releasingDate)->addDays($agingDays));

        // Update the record status
        $record->update([
            'status' => Status::APPROVED->value,
            'status_remarks' => $status_remarks,
            'due_date' => $due_date,
        ]);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => Status::IN_PROGRESS->value,
                'new_status' => Status::APPROVED->value,
                'status_remarks' => $status_remarks,
            ])
            ->log("Cash request {$record->request_no} was approved by {$user->name} ({$user->position})");

        // Send an email notification
        ApproveCashRequestByTreasuryJob::dispatch($record);

        Notification::make()
            ->title('Cash Request Update')
            ->body("Your cash request {$record->request_no} has been approved for releasing.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.cash-requests.track-status', ['record' => $record->id])),
            ])
            ->sendToDatabase($record->user);

        Notification::make()
            ->title('Cash Request Approved!')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.payment-processing.index');
    }

    /**
     * Reject the cash request, log the rejection, and dispatch notification.
     *
     * @param mixed $record
     * @param array<string, mixed> $data
     */
    public function rejectCashRequest($record, array $data)
    {
        $user = Auth::user();
        $status_remarks = app(StatusRemarkResolver::class)->rejectByPermissions($user, 'treasury');

        // Update the record status and save rejection reason
        $record->update([
            'status' => Status::REJECTED->value,
            'status_remarks' => $status_remarks,
            'reason_for_rejection' => $data['rejection_reason'],
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('rejected')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => Status::PENDING->value,
                'new_status' => Status::REJECTED->value,
                'status_remarks' => $status_remarks,
                'reason_for_rejection' => $data['rejection_reason'],
            ])
            ->log("Cash request {$record->request_no} was rejected by {$user->name} ({$user->position})");

        // Send an email notification
        RejectCashRequestJob::dispatch($record);

        Notification::make()
            ->title('Cash Request Update')
            ->body("Your cash request {$record->request_no} has been rejected.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.cash-requests.track-status', ['record' => $record->id])),
            ])
            ->sendToDatabase($record->user);

        Notification::make()
            ->title('Cash Request Rejected!')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.payment-processing.index');

    }

    /**
     * Persist the selected disbursement type and related details.
     *
     * @param mixed $record
     * @param array $data
     * @return void
     */
    public function saveDisbursementType(mixed $record, array $data): void
    {
        $basePayload = [
            'disbursement_type' => $data['disbursement_type'],
            'disbursement_added_by' => Auth::id(),
        ];

        $typePayload = match ($data['disbursement_type']) {
            DisbursementType::CHECK->value => $this->getCheckDisbursementPayload($data),
            DisbursementType::PAYROLL->value => $this->getPayrollDisbursementPayload($data),
            default => [],
        };

        $record->update(array_merge($basePayload, $typePayload));

        Notification::make()
            ->title('Disbursement details saved.')
            ->success()
            ->send();
    }

    /**
     * Build the payload for check disbursement fields.
     */
    public function getCheckDisbursementPayload(array $data): array
    {
        return [
            'check_branch_name' => $data['check_branch_name'] ?? null,
            'check_no' => $data['check_no'] ?? null,
            'dv_number' => $data['dv_number'] ?? null,
        ];
    }

    /**
     * Build the payload for payroll disbursement fields.
     */
    public function getPayrollDisbursementPayload(array $data): array
    {
        return [
            'dv_number' => $data['dv_number'] ?? null,
            'cut_off_date' => $data['cut_off_date'],
        ];
    }

    public function canSetDisbursement($record)
    {
        if ($record->nature_of_request == NatureOfRequestEnum::CASH_ADVANCE->value) {
            return $record->disbursement_added_by == null && $this->isTreasuryStaff() && !$record->is_override;
        }

        // It will only show if the nature of request is cash advance,
        // the disbursement_added_by is null, the role of current user is Treasury Staff,
        // already override and already verified/approved by the Treasury Manager.
        return $record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value
            && $record->disbursement_added_by == null
            && $this->isTreasuryStaff()
            && $record->is_override
            && $record->is_approved_by_treasury_manager;
    }

    public function overrideRequest($record, array $data = [])
    {
        $user = Auth::user();

        $payload = [
            'is_override' => true,
        ];

        if ($record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value) {
            $payload['proposed_due_date'] = $data['proposed_due_date'] ?? null;

            ForCashRelease::updateOrCreate(
                ['cash_request_id' => $record->id],
                [
                    'proposed_releasing_date' => $data['proposed_releasing_date'] ?? null,
                    'proposed_releasing_time_from' => $data['proposed_releasing_time_from'] ?? null,
                    'proposed_releasing_time_to' => $data['proposed_releasing_time_to'] ?? null,
                ]
            );
        }

        $record->update($payload);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('override')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => Status::PENDING->value,
                'new_status' => Status::IN_PROGRESS->value,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Cash request {$record->request_no} was override by {$user->name} ({$user->position})");

        // Notify the Treasury Manager once the Treasury Staff override the request.
        self::notifyTreasuryManager(
            $record,
            'Cash Request Overridden',
            "Cash request {$record->request_no} has been overridden."
        );

        return Notification::make()
            ->title('Cash Request Override!')
            ->success()
            ->send();
    }

    public function updateProposedDates($record, array $data): void
    {
        if ($record->nature_of_request !== NatureOfRequestEnum::CASH_ADVANCE->value || !$record->is_override) {
            return;
        }

        $record->update([
            'proposed_due_date' => $data['proposed_due_date'] ?? null,
        ]);

        ForCashRelease::updateOrCreate(
            ['cash_request_id' => $record->id],
            [
                'proposed_releasing_date' => $data['proposed_releasing_date'] ?? null,
                'proposed_releasing_time_from' => $data['proposed_releasing_time_from'] ?? null,
                'proposed_releasing_time_to' => $data['proposed_releasing_time_to'] ?? null,
            ]
        );

        Notification::make()
            ->title('Proposed dates updated.')
            ->success()
            ->send();
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

        return $user->roles()
            ->where('name', 'treasury_manager')
            ->exists();
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

        return $user->roles()
            ->where('name', 'treasury_staff')
            ->exists();
    }

    public function canEditProposedDueDate()
    {
        $isTreasuryStaff = $this->isTreasuryStaff();

        if ($isTreasuryStaff) {
            return true;
        }

        return false;
    }

    /**
     * Notify treasury manager about payment processing updates.
     * @param $record
     * @param string $title
     * @param string $body
     */
    public static function notifyTreasuryManager($record, string $title, string $body): void
    {
        $treasuryManagers = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'treasury_manager');
            })
            ->get();

        if ($treasuryManagers->isEmpty()) {
            return;
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.payment-processing.view', ['record' => $record->id])),
            ])
            ->sendToDatabase($treasuryManagers);
    }

    /**
     * Notify treasury staff about treasury manager approval updates.
     * @param $record
     * @param string $title
     * @param string $body
     */
    public static function notifyTreasuryStaff($record, string $title, string $body): void
    {
        $treasuryStaffs = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'treasury_staff');
            })
            ->get();

        if ($treasuryStaffs->isEmpty()) {
            return;
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.payment-processing.view', ['record' => $record->id])),
            ])
            ->sendToDatabase($treasuryStaffs);
    }

    public function canOverride($record): bool
    {
        if ($record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value && $record->disbursement_added_by == null) {
            return false;
        }

        if ($record->is_override) {
            return false;
        }

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        try {
            return $user->hasPermissionTo('can-override-payment-process-request');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            return false;
        }
    }

    public function rejectActivity($record, array $data): void
    {
        DB::transaction(function () use ($record, $data): void {
            $record->update([
                'status' => 'rejected',
                'rejection_remarks' => $data['rejection_remarks'],
            ]);

            $cashRequest = $record->cashRequest ?? null;
            $total = $cashRequest->activityLists()
                ->where('status', '!=', 'rejected')
                ->sum('requesting_amount');

            $cashRequest->update([
                'requesting_amount' => (float)$total,
            ]);

            $hasRemainingActivities = $cashRequest->activityLists()
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', 'rejected');
                })
                ->exists();

            if (!$hasRemainingActivities) {
                $statusRemarks = app(StatusRemarkResolver::class)->rejectByPermissions(Auth::user(), 'treasury');

                $cashRequest->update([
                    'status' => Status::REJECTED->value,
                    'status_remarks' => $statusRemarks,
                    'reason_for_rejection' => $data['rejection_remarks'],
                ]);
            }
        });

        Notification::make()
            ->title('Activity rejected')
            ->success()
            ->send();
    }

}
