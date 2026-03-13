<?php

namespace App\Services\Reimbursement;

use App\Enums\CashRequest\Status;
use App\Enums\Reimbursement\StatusRemarks;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ForReleasingReimbursementService
{
    public function canRelease($record): bool
    {
        return $record->status === Status::APPROVED->value
            && $record->status_remarks === StatusRemarks::FOR_RELEASING->value
            && $this->canManageRelease();
    }

    public function release($record)
    {
        $user = Auth::user();

        $record->update([
            'status' => Status::RELEASED->value,
            'status_remarks' => StatusRemarks::REIMBURSEMENT_RELEASED->value,
            'released_by' => $user?->id,
            'released_at' => now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('released')
            ->withProperties([
                'reimbursement_no' => $record->reimbursement_no,
                'new_status' => Status::RELEASED->value,
                'status_remarks' => StatusRemarks::REIMBURSEMENT_RELEASED->value,
            ])
            ->log("Reimbursement {$record->reimbursement_no} was released by {$user->name} ({$user->position})");

        if ($record->payee) {
            Notification::make()
                ->title('Reimbursement Released')
                ->body("Your reimbursement {$record->reimbursement_no} has been released.")
                ->actions([
                    NotificationAction::make('markAsRead')
                        ->button()
                        ->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.reimbursements.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->payee);
        }

        Notification::make()
            ->title('Reimbursement released')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-releasing-reimbursements.index');
    }

    private function canManageRelease(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles()->whereIn('name', ['treasury_staff', 'treasury_manager'])->exists();
    }
}
