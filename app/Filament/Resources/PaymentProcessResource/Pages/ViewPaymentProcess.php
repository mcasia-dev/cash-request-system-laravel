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
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
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
                        ->minDate(now()),

                    TimePicker::make('releasing_time_from')
                        ->label('Releasing Time From')
                        ->required()
                        ->default(now()),

                    TimePicker::make('releasing_time_to')
                        ->label('Releasing Time To')
                        ->required()
                        ->default(now()),
                ])
                ->action(fn(CashRequest $record, array $data): Notification => $this->approveCashRequest($record, $data))
                ->color('primary')
                ->visible(fn($record) => $this->getStatus($record))
                ->successRedirectUrl(route('filament.admin.resources.payment-processing.index')),

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
                ->action(fn(CashRequest $record, array $data): Notification => $this->rejectCashRequest($record, $data))
                ->visible(fn($record) => $this->getStatus($record))
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

    private function getStatus($record): bool
    {
        return $record->status === Status::IN_PROGRESS->value && $record->status_remarks === StatusRemarks::FOR_PAYMENT_PROCESSING->value;
    }

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

        return Notification::make()
            ->title('Cash Request Approved!')
            ->success()
            ->send();
    }

    private function getApprovedStatusRemarks(User $user)
    {
        return match (true) {
            $user->can('can-approve-as-treasury-manager')    => StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
            $user->can('can-approve-as-treasury-supervisor') => StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
            default                                          => 'Approved'
        };
    }

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

        return Notification::make()
            ->title('Cash Request Rejected!')
            ->success()
            ->send();
    }

    private function getRejectedStatusRemarks(User $user)
    {
        return match (true) {
            $user->can('can-reject-as-treasury-manager')    => StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
            $user->can('can-reject-as-treasury-supervisor') => StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
            default                                         => 'Rejected'
        };
    }
}
