<?php
namespace App\Filament\Resources\ForCashReleaseResource\Pages;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\ForCashReleaseResource;
use App\Jobs\RejectCashRequestJob;
use App\Jobs\ReleaseCashRequestByTreasuryJob;
use App\Models\CashRequest;
use App\Models\ForCashRelease;
use App\Models\ForLiquidation;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ViewForCashRelease extends ViewRecord
{
    protected static string $resource = ForCashReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // APPROVED BUTTON
            Action::make('Release')
                ->requiresConfirmation()
                ->form(fn($record) => [
                    Textarea::make('remarks')
                        ->required(),
                ])
                ->action(fn(ForCashRelease $record, array $data) => $this->releaseCashRequest($record, $data))
                ->color('primary')
                ->visible(fn($record) => $this->getStatus($record)),

            // REJECTION BUTTON
            Action::make('Reject')
                ->color('secondary')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Cash Request')
                ->modalSubmitActionLabel('Reject')
                ->action(fn(CashRequest $record, array $data) => $this->rejectCashRequest($record, $data))
                ->visible(fn($record) => $this->getStatus($record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Cash Request Details')
                    ->schema([
                        TextEntry::make('cashRequest.request_no')
                            ->label('Request No.'),

                        TextEntry::make('cashRequest.user.name')
                            ->label('Requestor'),

                        TextEntry::make('cashRequest.requesting_amount')
                            ->label('Total Requesting Amount')
                            ->money('PHP'),

                        TextEntry::make('cashRequest.status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending'    => 'warning',
                                'approved'   => 'success',
                                'released'   => 'info',
                                'liquidated' => 'primary',
                                'rejected'   => 'danger',
                                default      => 'gray',
                            }),
                    ])
                    ->columns(3),

                Section::make('Activity Information')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('cashRequest.activityLists')
                            ->label('')
                            ->schema([
                                TextEntry::make('activity_name')
                                    ->label('Activity Name'),

                                TextEntry::make('activity_date')
                                    ->label('Activity Date')
                                    ->date(),

                                TextEntry::make('activity_venue')
                                    ->label('Venue'),

                                TextEntry::make('purpose')
                                    ->label('Purpose'),

                                TextEntry::make('nature_of_request')
                                    ->label('Nature of Request')
                                    ->badge(),

                                TextEntry::make('requesting_amount')
                                    ->label('Requesting Amount')
                                    ->money('PHP'),

                                SpatieMediaLibraryImageEntry::make('attachment')
                                    ->label('Attached File/Image')
                                    ->collection('attachments')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Dates')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('cashRequest.created_at')
                            ->label('Date Requested')
                            ->date(),
                        TextEntry::make('releasing_date')
                            ->label('Releasing Date')
                            ->date(),

                        TextEntry::make('cashRequest.due_date')
                            ->label('Due Date')
                            ->date(),

                        TextEntry::make('cashRequest.date_released')
                            ->label('Date Released')
                            ->date(),

                        TextEntry::make('cashRequest.date_liquidated')
                            ->label('Date Liquidated')
                            ->date(),
                    ])
                    ->columns(3),
            ]);
    }

    /**
     * Determine if the cash request is eligible for releasing.
     *
     * @param mixed $record
     * @return bool
     */
    private function getStatus($record): bool
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
    private function releaseCashRequest($record, array $data)
    {
        $user           = Auth::user();
        $status_remarks = $this->getReleasedStatusRemarks($user);

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
    private function rejectCashRequest($record, array $data)
    {
        $user           = Auth::user();
        $status_remarks = $this->getRejectedStatusRemarks($user);

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

    /**
     * Resolve the released status remark based on the user's release role.
     *
     * @param User $user
     * @return string
     */
    private function getReleasedStatusRemarks(User $user)
    {
        return match (true) {
            $user->can('treasury-manager-can-release-cash-request')    => StatusRemarks::TREASURY_MANAGER_RELEASED_CASH_REQUESTED->value,
            $user->can('treasury-supervisor-can-release-cash-request') => StatusRemarks::TREASURY_SUPERVISOR_RELEASED_CASH_REQUESTED->value,
            default                                                    => 'Released'
        };
    }

    /**
     * Resolve the rejected status remark based on the user's rejection role.
     *
     * @param User $user
     * @return string
     */
    private function getRejectedStatusRemarks(User $user)
    {
        return match (true) {
            $user->can('can-reject-as-treasury-manager')    => StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
            $user->can('can-reject-as-treasury-supervisor') => StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
            default                                         => 'Rejected'
        };
    }
}
