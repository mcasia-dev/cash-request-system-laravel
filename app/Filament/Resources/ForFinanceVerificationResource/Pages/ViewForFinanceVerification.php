<?php
namespace App\Filament\Resources\ForFinanceVerificationResource\Pages;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\ForFinanceVerificationResource;
use App\Jobs\RejectCashRequestJob;
use App\Services\Remarks\StatusRemarkResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewForFinanceVerification extends ViewRecord
{
    protected static string $resource = ForFinanceVerificationResource::class;

    /**
     * Define the header actions for approving or rejecting a request in finance verification.
     */
    protected function getHeaderActions(): array
    {
        return [
            // APPROVED BUTTON
            Action::make('Approve')
                ->requiresConfirmation()
                ->form(fn($record) => [
                    TextInput::make('voucher_no')
                        ->label('Voucher No.')
                        ->required(),
                ])
                ->action(fn($record, array $data) => $this->approveRequest($record, $data))
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
                ->action(fn($record, array $data) => $this->rejectRequest($record, $data))
                ->visible(fn($record) => $this->getStatus($record)),
        ];
    }

    /**
     * Build the request detail infolist shown on the view page.
     */
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

                        TextEntry::make('nature_of_request')
                            ->label('Nature of Request')
                            ->badge(),

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
                    ->columns(3),

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
        return $record->status === Status::IN_PROGRESS->value && $record->status_remarks === StatusRemarks::FOR_FINANCE_VERIFICATION->value;
    }

    /**
     * Apply finance verification approval, log activity, and notify the user.
     */
    private function approveRequest($record, array $data)
    {
        $user                     = Auth::user();
        $approved_remarks_by_role = app(StatusRemarkResolver::class)->approveByPermissions($user, 'finance');

        // Update the record status
        $record->update([
            'voucher_no'     => $data['voucher_no'],
            'status'         => Status::IN_PROGRESS->value,
            'status_remarks' => StatusRemarks::FOR_PAYMENT_PROCESSING->value,
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
                'new_status'        => Status::IN_PROGRESS->value,
                'status_remarks'    => $approved_remarks_by_role,
            ])
            ->log("Cash request {$record->request_no} was verified and approved by {$user->name} ({$user->position})");

        // Send an email notification
        // ApproveCashRequestJob::dispatch($record);

        Notification::make()
            ->title('Cash Request Update')
            ->body("Your cash request {$record->request_no} has been approved by Finance and forwarded for payment processing.")
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

        return redirect()->route('filament.admin.resources.for-verification.index');
    }

    /**
     * Apply finance verification rejection, log activity, and notify the user.
     */
    private function rejectRequest($record, array $data)
    {
        $user           = Auth::user();
        $status_remarks = app(StatusRemarkResolver::class)->rejectByPermissions($user, 'finance');

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
                'previous_status'      => Status::IN_PROGRESS->value,
                'new_status'           => Status::REJECTED->value,
                'status_remarks'       => $status_remarks,
                'reason_for_rejection' => $data['rejection_reason'],
            ])
            ->log("Cash request {$record->request_no} was rejected by {$user->name} ({$user->position})");

        // Send an email notification
        RejectCashRequestJob::dispatch($record);

        Notification::make()
            ->title('Cash Request Rejected!')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-verification.index');

    }

}
