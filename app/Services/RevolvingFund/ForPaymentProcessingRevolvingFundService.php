<?php

namespace App\Services\RevolvingFund;

use App\Enums\CashRequest\DisbursementType;
use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

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
