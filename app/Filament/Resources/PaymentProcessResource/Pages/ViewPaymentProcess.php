<?php
namespace App\Filament\Resources\PaymentProcessResource\Pages;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Enums\NatureOfRequestEnum;
use App\Filament\Resources\PaymentProcessResource;
use App\Jobs\ApproveCashRequestByTreasuryJob;
use App\Jobs\RejectCashRequestJob;
use App\Models\CashRequest;
use App\Models\ForCashRelease;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
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

class ViewPaymentProcess extends ViewRecord
{
    protected static string $resource = PaymentProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // APPROVED BUTTON
            Action::make('Approve')
                ->requiresConfirmation()
                ->form(fn($record) => [
                    Textarea::make('remarks')
                        ->required(),

                    DatePicker::make('releasing_date')
                        ->label('Releasing Date')
                        ->required()
                        ->default(now())
                        ->minDate(now()->toDateString()),

                    TimePicker::make('releasing_time_from')
                        ->label('Releasing Time From')
                        ->required()
                        ->default(now()),

                    TimePicker::make('releasing_time_to')
                        ->label('Releasing Time To')
                        ->required()
                        ->default(now()),
                ])
                ->action(fn($record, array $data) => $this->approveCashRequest($record, $data))
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
                ->action(fn($record, array $data) => $this->rejectCashRequest($record, $data))
                ->visible(fn($record) => $this->getStatus($record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Cash Request Details')
                    ->schema([
                        TextEntry::make('request_no')
                            ->label('Request No.'),

                        TextEntry::make('user.name')
                            ->label('Requestor'),

                        TextEntry::make('requesting_amount')
                            ->label('Total Requesting Amount')
                            ->money('PHP'),

                        TextEntry::make('created_at')
                            ->label('Date Submitted')
                            ->dateTime('F d, Y h:i A'),

                        TextEntry::make('status')
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
                    ->columns(4),

                Section::make('Activity Information')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('activityLists')
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

                                TextEntry::make('cashRequest.nature_of_request')
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
            ]);
    }

    /**
     * Determine if the cash request is eligible for payment processing actions.
     *
     * @param mixed $record
     * @return bool
     */
    private function getStatus($record): bool
    {
        return $record->status === Status::IN_PROGRESS->value && $record->status_remarks === StatusRemarks::FOR_PAYMENT_PROCESSING->value;
    }

    /**
     * Approve the cash request, create the release record, set due date (if applicable),
     * log activity, and dispatch the approval notification.
     *
     * @param mixed $record
     * @param array<string, mixed> $data
     */
    private function approveCashRequest($record, array $data)
    {
        $user           = Auth::user();
        $status_remarks = $this->getApprovedStatusRemarks($user);

        // Insert the "For Releasing" Data
        ForCashRelease::create([
            'cash_request_id'     => $record->id,
            'processed_by'        => $user->id,
            'remarks'             => $data['remarks'],
            'releasing_date'      => $data['releasing_date'],
            'releasing_time_from' => $data['releasing_time_from'],
            'releasing_time_to'   => $data['releasing_time_to'],
            'date_processed'      => Carbon::now(),
        ]);

        // If the nature of request is "PETTY CASH", the due date will be 3 days after the releasing date. Else, return null (for the mean time).
        $due_date = $record->nature_of_request == NatureOfRequestEnum::PETTY_CASH->value ? Carbon::parse($data['releasing_date'])->addDays(3) : null;

        // Update the record status
        $record->update([
            'status'         => Status::APPROVED->value,
            'status_remarks' => $status_remarks,
            'due_date'       => $due_date,
        ]);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'request_no'        => $record->request_no,
                'activity_name'     => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status'   => Status::IN_PROGRESS->value,
                'new_status'        => Status::APPROVED->value,
                'status_remarks'    => $status_remarks,
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
     * Resolve the approved status remark based on the user's approval role.
     *
     * @param User $user
     * @return string
     */
    private function getApprovedStatusRemarks(User $user)
    {
        return match (true) {
            $user->can('can-approve-as-treasury-manager')    => StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
            $user->can('can-approve-as-treasury-supervisor') => StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
            default                                          => 'Approved'
        };
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
        $record->update([
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
