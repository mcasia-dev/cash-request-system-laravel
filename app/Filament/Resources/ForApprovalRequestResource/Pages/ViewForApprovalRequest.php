<?php
namespace App\Filament\Resources\ForApprovalRequestResource\Pages;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\ForFinanceVerificationResource;
use App\Filament\Resources\ForApprovalRequestResource;
use App\Filament\Resources\PaymentProcessResource;
use App\Jobs\ApproveCashRequestJob;
use App\Jobs\RejectCashRequestJob;
use App\Models\CashRequest;
use App\Services\CashRequestApprovalFlowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
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
                ->visible(function (CashRequest $record): bool {
                    $user = Auth::user();

                    if (! $user) {
                        return false;
                    }

                    return app(CashRequestApprovalFlowService::class)->userCanReview($record, $user);
                })
                ->requiresConfirmation()
                ->action(function (CashRequest $record) {
                    try {
                        $user           = Auth::user();
                        $previousStatus = $record->status;
                        $approvalResult = app(CashRequestApprovalFlowService::class)->applyApproval($record, $user);
                        $status_remarks = $approvalResult['status_remarks'];
                        $newStatus      = Status::IN_PROGRESS->value;

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
                                'status_remarks'    => $status_remarks,
                            ])
                            ->log("Cash request {$record->request_no} approval step was completed by {$user->name} ({$user->position})");

                        if ($approvalResult['is_final_step'] === true) {
                            ApproveCashRequestJob::dispatch($record->fresh());
                        }

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

                        if ($approvalResult['is_final_step'] !== true) {
                            return redirect()->route('filament.admin.resources.for-approval-requests.index');
                        }

                        $record = $record->fresh();

                        return redirect()->to(
                            $record->status_remarks === StatusRemarks::FOR_FINANCE_VERIFICATION->value
                                ? ForFinanceVerificationResource::getUrl('index')
                                : PaymentProcessResource::getUrl('index')
                        );
                    } catch (RuntimeException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('Reject')
                ->visible(function (CashRequest $record): bool {
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
                ->action(function (CashRequest $record, array $data) {
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
                            ->log("Cash request {$record->request_no} was rejected at an approval step by {$user->name} ({$user->position})");

                        // Send an email notification
                        RejectCashRequestJob::dispatch($record->fresh());

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
                }),
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
}
