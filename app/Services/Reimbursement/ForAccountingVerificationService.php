<?php

namespace App\Services\Reimbursement;

use App\Enums\CashRequest\Status;
use App\Enums\Reimbursement\StatusRemarks;
use App\Jobs\Reimbursement\SendAccountingVerificationUpdateJob;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ForAccountingVerificationService
{
    public function canOverride($record): bool
    {
        return $record->status === Status::IN_PROGRESS->value
            && $record->status_remarks === StatusRemarks::FOR_ACCOUNTING_VERIFICATION->value
            && Auth::user()?->hasRole('accounting_staff');
    }

    public function canManagerApprove($record): bool
    {
        return $record->status === Status::IN_PROGRESS->value
            && $record->status_remarks === StatusRemarks::ACCOUNTING_OVERRIDE_COMPLETED->value
            && Auth::user()?->hasRole('accounting_manager');
    }

    public function canFinalApprove($record): bool
    {
        return $record->status === Status::IN_PROGRESS->value
            && $record->status_remarks === StatusRemarks::ACCOUNTING_MANAGER_APPROVED->value
            && Auth::user()?->hasRole('accounting_staff');
    }

    public function override($record)
    {
        $user = Auth::user();

        $record->update([
            'status' => Status::IN_PROGRESS->value,
            'status_remarks' => StatusRemarks::ACCOUNTING_OVERRIDE_COMPLETED->value,
            'checked_by' => $user?->id,
            'checked_at' => now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('override')
            ->withProperties([
                'reimbursement_no' => $record->reimbursement_no,
                'status_remarks' => StatusRemarks::ACCOUNTING_OVERRIDE_COMPLETED->value,
            ])
            ->log("Reimbursement {$record->reimbursement_no} was overridden by {$user->name} ({$user->position})");

        $managers = User::query()
            ->role('accounting_manager')
            ->get();

        $this->notifyDatabase(
            recipients: $managers,
            title: 'Reimbursement Override Completed',
            body: "{$record->reimbursement_no} is ready for accounting manager approval.",
            viewRoute: route('filament.admin.resources.for-accounting-verifications.view', ['record' => $record->id]),
        );

        if ($record->payee) {
            $this->notifyDatabase(
                recipients: collect([$record->payee]),
                title: 'Reimbursement Update',
                body: "Your reimbursement {$record->reimbursement_no} has completed accounting override.",
                viewRoute: route('filament.admin.resources.reimbursements.view', ['record' => $record->id]),
            );
        }

        $this->queueEmail(
            recordId: $record->id,
            emails: $managers->pluck('email')->filter()->values()->all(),
            subject: "Reimbursement {$record->reimbursement_no} - Manager Approval Needed",
            message: "{$record->reimbursement_no} is ready for your accounting manager approval.",
            actionUrl: route('filament.admin.resources.for-accounting-verifications.view', ['record' => $record->id]),
        );

        if ($record->payee?->email) {
            $this->queueEmail(
                recordId: $record->id,
                emails: [$record->payee->email],
                subject: "Reimbursement {$record->reimbursement_no} - Accounting Update",
                message: "Your reimbursement has completed accounting override and is now under manager review.",
                actionUrl: route('filament.admin.resources.reimbursements.view', ['record' => $record->id]),
            );
        }

        Notification::make()
            ->title('Override completed')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-accounting-verifications.index');
    }

    public function managerApprove($record)
    {
        $user = Auth::user();

        $record->update([
            'status' => Status::IN_PROGRESS->value,
            'status_remarks' => StatusRemarks::ACCOUNTING_MANAGER_APPROVED->value,
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'reimbursement_no' => $record->reimbursement_no,
                'status_remarks' => StatusRemarks::ACCOUNTING_MANAGER_APPROVED->value,
            ])
            ->log("Reimbursement {$record->reimbursement_no} was approved by accounting manager {$user->name} ({$user->position})");

        $staff = User::query()
            ->role('accounting_staff')
            ->get();

        $this->notifyDatabase(
            recipients: $staff,
            title: 'Reimbursement Manager Approval Completed',
            body: "{$record->reimbursement_no} is ready for final accounting staff approval.",
            viewRoute: route('filament.admin.resources.for-accounting-verifications.view', ['record' => $record->id]),
        );

        if ($record->payee) {
            $this->notifyDatabase(
                recipients: collect([$record->payee]),
                title: 'Reimbursement Update',
                body: "Your reimbursement {$record->reimbursement_no} was approved by accounting manager.",
                viewRoute: route('filament.admin.resources.reimbursements.view', ['record' => $record->id]),
            );
        }

        $this->queueEmail(
            recordId: $record->id,
            emails: $staff->pluck('email')->filter()->values()->all(),
            subject: "Reimbursement {$record->reimbursement_no} - Final Approval Needed",
            message: "{$record->reimbursement_no} is now ready for final accounting staff approval.",
            actionUrl: route('filament.admin.resources.for-accounting-verifications.view', ['record' => $record->id]),
        );

        if ($record->payee?->email) {
            $this->queueEmail(
                recordId: $record->id,
                emails: [$record->payee->email],
                subject: "Reimbursement {$record->reimbursement_no} - Accounting Update",
                message: "Your reimbursement was approved by accounting manager and is now awaiting final accounting staff approval.",
                actionUrl: route('filament.admin.resources.reimbursements.view', ['record' => $record->id]),
            );
        }

        Notification::make()
            ->title('Manager approval completed')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-accounting-verifications.index');
    }

    public function finalApprove($record)
    {
        $user = Auth::user();

        $record->update([
            'status' => Status::IN_PROGRESS->value,
            'status_remarks' => StatusRemarks::FOR_PAYMENT_PROCESSING->value,
            'checked_by' => $user?->id,
            'checked_at' => now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'reimbursement_no' => $record->reimbursement_no,
                'status_remarks' => StatusRemarks::FOR_PAYMENT_PROCESSING->value,
            ])
            ->log("Reimbursement {$record->reimbursement_no} was finalized by accounting staff {$user->name} ({$user->position})");

        if ($record->payee) {
            $this->notifyDatabase(
                recipients: collect([$record->payee]),
                title: 'Reimbursement Update',
                body: "Your reimbursement {$record->reimbursement_no} passed accounting verification.",
                viewRoute: route('filament.admin.resources.reimbursements.view', ['record' => $record->id]),
            );

            if ($record->payee?->email) {
                $this->queueEmail(
                    recordId: $record->id,
                    emails: [$record->payee->email],
                    subject: "Reimbursement {$record->reimbursement_no} - Accounting Verified",
                    message: "Your reimbursement passed accounting verification and is now forwarded for payment processing.",
                    actionUrl: route('filament.admin.resources.reimbursements.view', ['record' => $record->id]),
                );
            }
        }

        Notification::make()
            ->title('Accounting verification completed')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-accounting-verifications.index');
    }

    private function notifyDatabase($recipients, string $title, string $body, string $viewRoute): void
    {
        if ($recipients->isEmpty()) {
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
                    ->url($viewRoute),
            ])
            ->sendToDatabase($recipients);
    }

    private function queueEmail(int $recordId, array $emails, string $subject, string $message, string $actionUrl): void
    {
        if (empty($emails)) {
            return;
        }

        SendAccountingVerificationUpdateJob::dispatch(
            reimbursementId: $recordId,
            emails: $emails,
            subjectLine: $subject,
            messageBody: $message,
            actionUrl: $actionUrl,
        );
    }
}
