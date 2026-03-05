<?php
namespace App\Services\CashRequest;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Jobs\RejectCashRequestJob;
use App\Jobs\ReleaseCashRequestByTreasuryJob;
use App\Models\ForLiquidation;
use App\Services\Remarks\StatusRemarkResolver;
use App\Traits\GenerateSettingTrait;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForCashReleaseService
{
    use GenerateSettingTrait;

    /**
     * Determine if the cash request is eligible for releasing.
     *
     * @param mixed $record
     * @return bool
     */
    public function getStatus($record): bool
    {
        return $record->cashRequest->status === Status::APPROVED->value && $record->cashRequest->status_remarks === StatusRemarks::FOR_RELEASING->value;
    }

    /**
     * Release the cash request, update related records, log activity,
     * and dispatch the treasury release notification.
     *
     * @param mixed $record
     * @param array<string, mixed> $data
     */
    public function releaseCashRequest($record, array $data)
    {
        $user           = Auth::user();
        $status_remarks = app(StatusRemarkResolver::class)->releaseByPermissions($user);

        // Update the released date and released_by column
        $record->update([
            'released_by'   => $user->id,
            'date_released' => Carbon::now(),
        ]);

        // Update the cash request record status
        $record->cashRequest
            ->update([
                'status'         => Status::RELEASED->value,
                'status_remarks' => $status_remarks,
                'date_released'  => Carbon::now(),
            ]);

        // Create data for "for_liquidations" table
        ForLiquidation::create([
            'cash_request_id' => $record->cash_request_id,
            'remarks'         => $data['remarks'],
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($record->cashRequest ?? $record)
            ->event('released')
            ->withProperties([
                'request_no'        => $record->request_no,
                'activity_name'     => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status'   => Status::APPROVED->value,
                'new_status'        => Status::RELEASED->value,
                'status_remarks'    => $status_remarks,
            ])
            ->log("Cash request {$record->request_no} is released and now ready.");

        // Send an email notification
        ReleaseCashRequestByTreasuryJob::dispatch($record->cashRequest);

        Notification::make()
            ->title('Cash Request Update')
            ->body("Your cash request {$record->cashRequest->request_no} has been released.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),

                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.cash-requests.track-status', ['record' => $record->cashRequest->id])),
            ])
            ->sendToDatabase($record->cashRequest->user);

        Notification::make()
            ->title('Cash Request Released!')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-cash-releases.index');
    }

    /**
     * Reject the cash request, log the rejection, and dispatch notification.
     *
     * @param mixed $record
     * @param array<string, mixed> $data
     */
    public function rejectCashRequest($record, array $data)
    {
        $user           = Auth::user();
        $status_remarks = app(StatusRemarkResolver::class)->rejectByPermissions($user, 'treasury');

        // Update the record status and save rejection reason
        $record->cashRequest
            ->update([
                'status'               => Status::REJECTED->value,
                'status_remarks'       => $status_remarks,
                'reason_for_rejection' => $data['rejection_reason'],
            ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('rejected')
            ->withProperties([
                'request_no'           => $record->request_no,
                'activity_name'        => $record->activity_name,
                'requesting_amount'    => $record->requesting_amount,
                'previous_status'      => Status::PENDING->value,
                'new_status'           => Status::REJECTED->value,
                'status_remarks'       => $status_remarks,
                'reason_for_rejection' => $data['rejection_reason'],
            ])
            ->log("Cash request {$record->request_no} was rejected by {$user->name} ({$user->position})");

        // Send an email notification
        RejectCashRequestJob::dispatch($record);

        Notification::make()
            ->title('Cash Request Update')
            ->body("Your cash request {$record->cashRequest->request_no} has been rejected.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.cash-requests.track-status', ['record' => $record->cashRequest->id])),
            ])
            ->sendToDatabase($record->cashRequest->user);

        Notification::make()
            ->title('Cash Request Rejected!')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-cash-releases.index');
    }

    public function rejectActivity($record, array $data): void
    {
        DB::transaction(function () use ($record, $data): void {
            $record->update([
                'status'            => 'rejected',
                'rejection_remarks' => $data['rejection_remarks'],
            ]);

            $cashRequest = $record->cashRequest ?? null;
            $total       = $cashRequest->activityLists()
                ->where('status', '!=', 'rejected')
                ->sum('requesting_amount');

            $cashRequest->update([
                'requesting_amount' => (float) $total,
            ]);

            $hasRemainingActivities = $cashRequest->activityLists()
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', 'rejected');
                })
                ->exists();

            if (! $hasRemainingActivities) {
                $statusRemarks = app(StatusRemarkResolver::class)->rejectByPermissions(Auth::user(), 'treasury');

                $cashRequest->update([
                    'status'               => Status::REJECTED->value,
                    'status_remarks'       => $statusRemarks,
                    'reason_for_rejection' => $data['rejection_remarks'],
                ]);
            }
        });

        Notification::make()
            ->title('Activity rejected')
            ->success()
            ->send();
    }

    public function changeReleasingDate($record, $data)
    {
        $user                 = Auth::user();
        $newReleasingDate     = Carbon::parse($data['releasing_date'])->toDateString();
        $currentReleasingDate = $record->releasing_date?->toDateString();
        $didChangeDate        = $currentReleasingDate !== $newReleasingDate;
        $attempts             = (int) ($record->update_releasing_date_attempt ?? 0);

        if ($attempts >= 3) {
            activity()
                ->causedBy($user)
                ->performedOn($record->cashRequest ?? $record)
                ->event('releasing_date_update_blocked')
                ->withProperties([
                    'cash_request_id' => $record->cash_request_id,
                    'request_no'      => $record->cashRequest?->request_no,
                    'attempts'        => $attempts,
                    'reason'          => 'limit_reached',
                ])
                ->log("Releasing date update blocked due to attempt limit for request {$record->cashRequest?->request_no}.");

            Notification::make()
                ->title('Releasing date update limit reached.')
                ->danger()
                ->send();

            return;
        }

        $payload = [
            'releasing_date'      => $newReleasingDate,
            'releasing_time_from' => $data['releasing_time_from'],
            'releasing_time_to'   => $data['releasing_time_to'],
            'date_edited'         => Carbon::now(),
            'remarks'             => $data['remarks'] ?? null,
            'edited_by'           => Auth::id(),
        ];

        if ($didChangeDate) {
            $payload['update_releasing_date_attempt'] = $attempts + 1;
        }

        $oldTimeFrom = $record->releasing_time_from
            ? Carbon::parse($record->releasing_time_from)->format('H:i:s')
            : null;
        $oldTimeTo   = $record->releasing_time_to
            ? Carbon::parse($record->releasing_time_to)->format('H:i:s')
            : null;
        $newAttempts = $didChangeDate ? ($attempts + 1) : $attempts;

        $record->update($payload);

        $agingDays  = $this->getAgingDaysFromSettings();
        $newDueDate = Carbon::parse($newReleasingDate)->addDays($agingDays);
        if ($newDueDate->isWeekend()) {
            $newDueDate = $newDueDate->next(Carbon::MONDAY);
        }

        $record->cashRequest->update([
            'due_date' => $newDueDate,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record->cashRequest ?? $record)
            ->event('releasing_date_updated')
            ->withProperties([
                'cash_request_id'         => $record->cash_request_id,
                'request_no'              => $record->cashRequest?->request_no,
                'old_releasing_date'      => $currentReleasingDate,
                'new_releasing_date'      => $newReleasingDate,
                'old_releasing_time_from' => $oldTimeFrom,
                'new_releasing_time_from' => $data['releasing_time_from'] ?? null,
                'old_releasing_time_to'   => $oldTimeTo,
                'new_releasing_time_to'   => $data['releasing_time_to'] ?? null,
                'attempt_before'          => $attempts,
                'attempt_after'           => $newAttempts,
                'date_was_changed'        => $didChangeDate,
                'remarks'                 => $data['remarks'] ?? null,
            ])
            ->log("Releasing schedule updated for request {$record->cashRequest?->request_no}.");

        Notification::make()
            ->title('Releasing schedule updated.')
            ->success()
            ->send();
    }
}
