<?php

namespace App\Services\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ForReleasingRevolvingFundService
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
            'status_remarks' => StatusRemarks::FUND_RELEASED->value,
            'released_by' => $user?->id,
            'released_at' => now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('released')
            ->withProperties([
                'fund_code' => $record->fund_code,
                'new_status' => Status::RELEASED->value,
                'status_remarks' => StatusRemarks::FUND_RELEASED->value,
            ])
            ->log("Revolving fund {$record->fund_code} was released by {$user->name} ({$user->position})");

        if ($record->addedBy) {
            Notification::make()
                ->title('Revolving Fund Released')
                ->body("Your revolving fund {$record->fund_code} has been released.")
                ->actions([
                    NotificationAction::make('markAsRead')
                        ->button()
                        ->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.revolving-funds.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->addedBy);
        }

        Notification::make()
            ->title('Revolving fund released')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-releasing-revolving-funds.index');
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
