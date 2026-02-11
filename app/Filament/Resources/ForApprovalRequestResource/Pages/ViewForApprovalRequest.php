<?php
namespace App\Filament\Resources\ForApprovalRequestResource\Pages;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\ForApprovalRequestResource;
use App\Jobs\ApproveCashRequestJob;
use App\Jobs\RejectCashRequestJob;
use App\Models\CashRequest;
use App\Services\ApprovalStatusResolver;
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

class ViewForApprovalRequest extends ViewRecord
{
    protected static string $resource = ForApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Approve')
                ->visible(fn($record) => $record->status === Status::PENDING->value)
                ->requiresConfirmation()
                ->action(function (CashRequest $record) {
                    $user           = Auth::user();
                    $status_remarks = ApprovalStatusResolver::approve($user);

                    // Update the record status
                    $record->update([
                        'status'         => Status::IN_PROGRESS->value,
                        'status_remarks' => $status_remarks,
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
                            'previous_status'   => Status::PENDING->value,
                            'new_status'        => Status::IN_PROGRESS->value,
                            'status_remarks'    => $status_remarks,
                        ])
                        ->log("Cash request {$record->request_no} was approved by {$user->name} ({$user->position})");

                    // Send an email notification
                    ApproveCashRequestJob::dispatch($record);

                    Notification::make()
                        ->title('Cash Request Approved!')
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.for-approval-requests.index');
                }),

            Action::make('Reject')
                ->visible(fn($record) => $record->status === Status::PENDING->value)
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
                    $user           = Auth::user();
                    $status_remarks = ApprovalStatusResolver::reject($user);

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
                        ->title('Cash Request Rejected!')
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.for-approval-requests.index');
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
