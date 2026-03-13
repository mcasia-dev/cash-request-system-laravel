<?php

namespace App\Services\Reimbursement;

use App\Enums\CashRequest\DisbursementType;
use App\Enums\CashRequest\Status;
use App\Enums\Reimbursement\StatusRemarks;
use App\Jobs\Reimbursement\ApproveReimbursementJob;
use App\Jobs\Reimbursement\RejectReimbursementJob;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ForPaymentProcessingReimbursementService
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
            && ! $record->is_override
            && $record->disbursement_type !== null;
    }

    public function canApproveRequest($record): bool
    {
        return $this->getStatus($record)
            && $this->isTreasuryManager()
            && $record->is_override
            && ! $record->is_approved_by_treasury_manager;
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
                'reimbursement_no' => $record->reimbursement_no,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Reimbursement {$record->reimbursement_no} was overridden by {$user->name} ({$user->position})");

        $managers = User::query()
            ->role('treasury_manager')
            ->get();

        if ($managers->isNotEmpty()) {
            Notification::make()
                ->title('Reimbursement Overridden')
                ->body("{$record->reimbursement_no} has been overridden and is waiting for manager approval.")
                ->actions([
                    NotificationAction::make('markAsRead')->button()->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.for-payment-processing-reimbursements.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($managers);
        }

        Notification::make()
            ->title('Override completed')
            ->success()
            ->send();
    }

    public function approveReimbursement($record): void
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
                'reimbursement_no' => $record->reimbursement_no,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Reimbursement {$record->reimbursement_no} was approved by treasury manager {$user->name} ({$user->position})");

        $staff = User::query()
            ->role('treasury_staff')
            ->get();

        if ($staff->isNotEmpty()) {
            Notification::make()
                ->title('Reimbursement Approved by Manager')
                ->body("{$record->reimbursement_no} is now ready for release form approval.")
                ->actions([
                    NotificationAction::make('markAsRead')->button()->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.for-payment-processing-reimbursements.view', ['record' => $record->id])),
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
                'reimbursement_no' => $record->reimbursement_no,
                'new_status' => Status::APPROVED->value,
                'status_remarks' => StatusRemarks::FOR_RELEASING->value,
            ])
            ->log("Reimbursement {$record->reimbursement_no} was approved for releasing by {$user->name} ({$user->position})");

        ApproveReimbursementJob::dispatch($record->fresh(['payee']));

        if ($record->payee) {
            Notification::make()
                ->title('Reimbursement Update')
                ->body("Your reimbursement {$record->reimbursement_no} has been approved for releasing.")
                ->actions([
                    NotificationAction::make('markAsRead')->button()->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.reimbursements.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->payee);
        }

        Notification::make()
            ->title('Reimbursement approved')
            ->success()
            ->send();
    }

    public function rejectReimbursement($record, array $data): void
    {
        $user = Auth::user();

        $record->update([
            'status' => Status::REJECTED->value,
            'status_remarks' => StatusRemarks::TREASURY_REJECTED->value,
            'reason_for_rejection' => $data['rejection_reason'],
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('rejected')
            ->withProperties([
                'reimbursement_no' => $record->reimbursement_no,
                'new_status' => Status::REJECTED->value,
                'status_remarks' => StatusRemarks::TREASURY_REJECTED->value,
                'reason_for_rejection' => $data['rejection_reason'],
            ])
            ->log("Reimbursement {$record->reimbursement_no} was rejected by {$user->name} ({$user->position})");

        RejectReimbursementJob::dispatch($record->fresh(['payee']));

        if ($record->payee) {
            Notification::make()
                ->title('Reimbursement Update')
                ->body("Your reimbursement {$record->reimbursement_no} has been rejected.")
                ->actions([
                    NotificationAction::make('markAsRead')->button()->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.reimbursements.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->payee);
        }

        Notification::make()
            ->title('Reimbursement rejected')
            ->success()
            ->send();
    }

    public function isTreasuryManager(): bool
    {
        $user = Auth::user();

        if (! $user) {
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

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles()->where('name', 'treasury_staff')->exists();
    }
}
