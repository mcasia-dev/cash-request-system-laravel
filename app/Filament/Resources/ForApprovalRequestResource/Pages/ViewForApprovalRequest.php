<?php
namespace App\Filament\Resources\ForApprovalRequestResource\Pages;

use App\Enums\CashRequest\DisbursementType;
use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Enums\NatureOfRequestEnum;
use App\Filament\Resources\ForApprovalRequestResource;
use App\Jobs\ApproveCashRequestJob;
use App\Jobs\RejectCashRequestJob;
use App\Models\User;
use App\Services\CashRequestApprovalFlowService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ViewForApprovalRequest extends ViewRecord
{
    protected static string $resource = ForApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Approve')
                ->visible(function ($record): bool {
                    $user = Auth::user();

                    if (! $user) {
                        return false;
                    }

                    return app(CashRequestApprovalFlowService::class)->userCanReview($record, $user);
                })
                ->requiresConfirmation()
                ->action(fn($record) => $this->approveForApprovalRequest($record)),

            Action::make('Reject')
                ->visible(function ($record): bool {
                    $user = Auth::user();

                    if (! $user) {
                        return false;
                    }

                    return app(CashRequestApprovalFlowService::class)->userCanReview($record, $user);
                })
                ->color('secondary')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Cash Request')
                ->modalSubmitActionLabel('Reject')
                ->action(fn($record, array $data) => $this->rejectForApprovalRequest($record, $data)),
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

                        TextEntry::make('status_remarks')
                            ->badge()
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

    private function approveForApprovalRequest($record)
    {
        try {
            $user                     = Auth::user();
            $previousStatus           = $record->status;
            $approvalResult           = app(CashRequestApprovalFlowService::class)->applyApproval($record, $user);
            $approved_remarks_by_role = $approvalResult['approved_remarks_by_role'] ?? $approvalResult['status_remarks'];
            $newStatus                = Status::IN_PROGRESS->value;

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($record)
                ->event('approved')
                ->withProperties([
                    'request_no'        => $record->request_no,
                    'activity_name'     => $record->activity_name,
                    'requesting_amount' => $record->requesting_amount,
                    'previous_status'   => $previousStatus,
                    'new_status'        => $newStatus,
                    'status_remarks'    => $approved_remarks_by_role,
                ])
                ->log("Cash request {$record->request_no} approval step was completed by {$user->name} ({$user->position})");

            if ($approvalResult['is_final_step'] === true) {
                ApproveCashRequestJob::dispatch($record->fresh());

                $newRecord = $record->fresh();

                if ($newRecord->status_remarks === StatusRemarks::FOR_PAYMENT_PROCESSING->value) {
                    $this->notifyPaymentProcessApprovers($newRecord);
                }
            }

            // Notify Users through Database Notifications
            Notification::make()
                ->title('Cash Request Update')
                ->body("Your cash request {$record->request_no} has been approved.")
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
                ->title(
                    $approvalResult['is_final_step']
                        ? (
                        $record->fresh()->status_remarks === StatusRemarks::FOR_FINANCE_VERIFICATION->value
                            ? 'Final approval completed. Sent to Finance Verification.'
                            : 'Final approval completed. Sent to Payment Processing.'
                    )
                        : 'Approval step completed.'
                )
                ->success()
                ->send();

            return redirect()->route('filament.admin.resources.for-approval-requests.index');
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function rejectForApprovalRequest($record, array $data)
    {
        try {
            $user           = Auth::user();
            $previousStatus = $record->status;
            $status_remarks = app(CashRequestApprovalFlowService::class)->applyRejection($record, $user, $data['rejection_reason']);
            $newStatus      = Status::REJECTED->value;

            // Log activity
            activity()
                ->causedBy($user)
                ->performedOn($record)
                ->event('rejected')
                ->withProperties([
                    'request_no'           => $record->request_no,
                    'activity_name'        => $record->activity_name,
                    'requesting_amount'    => $record->requesting_amount,
                    'previous_status'      => $previousStatus,
                    'new_status'           => $newStatus,
                    'status_remarks'       => $status_remarks,
                    'reason_for_rejection' => $data['rejection_reason'],
                ])
                ->log("Cash request {$record->request_no} was rejected by {$user->name} ({$user->position})");

            // Send an email notification
            RejectCashRequestJob::dispatch($record->fresh());

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

            return redirect()->route('filament.admin.resources.for-approval-requests.index');
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function notifyPaymentProcessApprovers($record): void
    {
        $approvers = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['treasury_manager', 'treasury_supervisor']);
            })
            ->get();

        if ($approvers->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Cash Request For Payment Processing')
            ->body("{$record->request_no} is ready for payment processing.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),

                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.payment-processing.view', ['record' => $record->id])),
            ])
            ->sendToDatabase($approvers);
    }
}
