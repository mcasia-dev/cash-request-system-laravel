<?php
namespace App\Filament\Resources\ForApprovalRequestResource\Pages;

use App\Models\CashRequest;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use App\Enums\CashRequest\Status;
use App\Jobs\RejectCashRequestJob;
use App\Jobs\ApproveCashRequestJob;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\ForApprovalRequestResource;

class ViewForApprovalRequest extends ViewRecord
{
    protected static string $resource = ForApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Approve')
                ->requiresConfirmation()
                ->action(function (CashRequest $record) {
                    // Update the record status
                    $record->update(['status' => Status::APPROVED->value]);

                    // Log activity
                    activity()
                        ->causedBy(Auth::user())
                        ->performedOn($record)
                        ->event('approved')
                        ->withProperties([
                            'request_no'        => $record->request_no,
                            'activity_name'     => $record->activity_name,
                            'requesting_amount' => $record->requesting_amount,
                            'previous_status'   => 'pending',
                            'new_status'        => 'approved',
                        ])
                        ->log("Cash request {$record->request_no} was approved by " . Auth::user()->name);

                    // Send an email notification
                    ApproveCashRequestJob::dispatch($record);

                    Notification::make()
                        ->title('Cash Request Approved!')
                        ->success()
                        ->send();
                })
                ->successRedirectUrl(route('filament.admin.resources.for-approval-requests.index')),

            Action::make('Reject')
                ->color('danger')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Cash Request')
                ->modalSubmitActionLabel('Reject')
                ->action(function (CashRequest $record, array $data) {
                    // Update the record status and save rejection reason
                    $record->update([
                        'status' => Status::REJECTED->value,
                        'reason_for_rejection' => $data['rejection_reason'],
                    ]);

                    // Log activity
                    activity()
                        ->causedBy(Auth::user())
                        ->performedOn($record)
                        ->event('rejected')
                        ->withProperties([
                            'request_no'         => $record->request_no,
                            'activity_name'      => $record->activity_name,
                            'requesting_amount'  => $record->requesting_amount,
                            'previous_status'    => 'pending',
                            'new_status'         => 'rejected',
                            'reason_for_rejection' => $data['rejection_reason'],
                        ])
                        ->log("Cash request {$record->request_no} was rejected by " . Auth::user()->name);

                    // Send an email notification
                    RejectCashRequestJob::dispatch($record);

                    Notification::make()
                        ->title('Cash Request Rejected!')
                        ->success()
                        ->send();
                })
                ->successRedirectUrl(route('filament.admin.resources.for-approval-requests.index')),

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
                        TextEntry::make('nature_of_request')
                            ->badge(),
                    ])
                    ->columns(2),

                Section::make('Activity Information')
                    ->schema([
                        TextEntry::make('activity_name')
                            ->label('Activity Name'),
                        TextEntry::make('activity_date')
                            ->label('Activity Date')
                            ->date(),
                        TextEntry::make('activity_venue')
                            ->label('Venue'),
                        TextEntry::make('purpose')
                            ->label('Purpose')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Payment Details')
                    ->schema([
                        TextEntry::make('requesting_amount')
                            ->label('Requesting Amount')
                            ->money('PHP'),
                        TextEntry::make('nature_of_payment')
                            ->label('Payment Type'),
                        TextEntry::make('payee'),
                        TextEntry::make('payment_to')
                            ->label('Payment To'),
                        TextEntry::make('bank_name')
                            ->label('Bank'),
                        TextEntry::make('bank_account_no')
                            ->label('Account Number'),
                        TextEntry::make('account_type')
                            ->label('Account Type'),
                    ])
                    ->columns(2),

                Section::make('Dates')
                    ->schema([
                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date(),
                        TextEntry::make('date_released')
                            ->label('Date Released')
                            ->date(),
                        TextEntry::make('date_liquidated')
                            ->label('Date Liquidated')
                            ->date(),
                    ])
                    ->columns(3),

                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
