<?php

namespace App\Services\CashRequest;

use App\Enums\CashRequest\Status;
use App\Models\CashRequest;
use App\Models\User;
use Filament\Notifications\Notification;

class CancellationService
{
    /**
     * Cancel a cash request and log activity.
     *
     * @param array<string, mixed> $data
     */
    public function cancel(CashRequest $record, array $data, User $user): void
    {
        $record->update([
            'status'               => Status::CANCELLED->value,
            'reason_for_cancelling' => $data['reason_for_cancelling'],
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('cancelled')
            ->withProperties([
                'request_no'           => $record->request_no,
                'activity_name'        => $record->activity_name,
                'requesting_amount'    => $record->requesting_amount,
                'previous_status'      => Status::PENDING->value,
                'new_status'           => Status::CANCELLED->value,
                'reason_for_cancelling' => $data['reason_for_cancelling'],
            ])
            ->log("Cash request {$record->request_no} was cancelled by {$user->name}");

        Notification::make()
            ->title('Cash Request Cancelled!')
            ->success()
            ->send();
    }
}
