<?php

namespace App\Filament\Resources\PaymentProcessResource\Pages;

use App\Enums\CashRequest\DisbursementType;
use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Enums\NatureOfRequestEnum;
use App\Filament\Resources\PaymentProcessResource;
use App\Jobs\ApproveCashRequestByTreasuryJob;
use App\Jobs\RejectCashRequestJob;
use App\Models\ForCashRelease;
use App\Models\User;
use App\Services\Remarks\StatusRemarkResolver;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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

class ViewPaymentProcess extends ViewRecord
{
    protected static string $resource = PaymentProcessResource::class;

    /**
     * Define the header actions for setting disbursement, approving, or rejecting.
     */
    protected function getHeaderActions(): array
    {
        return [
            // SET DISBURSEMENT BUTTON
            Action::make('set_disbursement')
                ->label('Set Disbursement')
                ->visible(fn($record) => $record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value && $record->disbursement_type != null)
                ->color('gray')
                ->form($this->getDisbursementTypeFormSchema())
                ->action(fn($record, array $data) => $this->saveDisbursementType($record, $data)),

            // OVERRIDE BUTTON
            Action::make('override')
                ->label('Override')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn($record) => $this->overrideRequest($record))
                ->visible(fn($record) => Auth::user()->hasPermissionTo('can-override-payment-process-request') && !$record->is_override),

            // APPROVED BUTTON
            Action::make('Approve')
                ->requiresConfirmation()
                ->form(fn($record) => $this->getApproveFormSchema($record))
                ->action(fn($record, array $data) => $this->approveCashRequest($record, $data))
                ->color('primary')
                ->hidden(fn($record) => $record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value && $record->disbursement_type == null)
                ->visible(fn($record) => $this->getStatus($record) && $this->isTreasuryManager() && $record->is_override),

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

    /**
     * Build the request detail infolist and activity sections for the view page.
     */
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Cash Request Details')
                    ->schema([
                        TextEntry::make('request_no')
                            ->label('Request No.')
                            ->copyable(),

                        TextEntry::make('user.name')
                            ->label('Requestor'),

                        TextEntry::make('requesting_amount')
                            ->label('Total Requesting Amount')
                            ->money('PHP'),

                        TextEntry::make('nature_of_request')
                            ->label('Nature of Request')
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('created_at')
                            ->label('Date Submitted')
                            ->dateTime('F d, Y h:i A'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'released' => 'info',
                                'liquidated' => 'primary',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('status_remarks')
                            ->badge(),
                    ])
                    ->columns(4),

                Section::make('Activity Information')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('activityLists')
                            ->label('')
                            ->getStateUsing(fn($record) => $record->activityLists()
                                ->where('status', '!=', 'rejected')
                                ->get())
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

                        Section::make('Disbursement Method')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                TextEntry::make('disbursement_type')
                                    ->label('Disbursement Type')
                                    ->badge()
                                    ->placeholder('Not yet set'),

                                TextEntry::make('requesting_amount')
                                    ->label('Amount')
                                    ->money('PHP'),

                                TextEntry::make('check_branch_name')
                                    ->label('Check Branch Name')
                                    ->visible(fn($record) => $record->disbursement_type === DisbursementType::CHECK->value)
                                    ->placeholder('-'),

                                TextEntry::make('check_no')
                                    ->label('Check No.')
                                    ->visible(fn($record) => $record->disbursement_type === DisbursementType::CHECK->value)
                                    ->placeholder('-'),

                                TextEntry::make('voucher_no')
                                    ->label('Voucher No.')
                                    ->visible(fn($record) => $record->disbursement_type === DisbursementType::CHECK->value)
                                    ->placeholder('-'),

                                TextEntry::make('cut_off_date')
                                    ->label('Cut-off Date')
                                    ->date()
                                    ->visible(fn($record) => $record->disbursement_type === DisbursementType::PAYROLL->value)
                                    ->placeholder('-'),

                                TextEntry::make('payroll_credit')
                                    ->label('Payroll Credit')
                                    ->money('PHP')
                                    ->visible(fn($record) => $record->disbursement_type === DisbursementType::PAYROLL->value)
                                    ->placeholder('-'),

                                TextEntry::make('disbursementAddedBy.name')
                                    ->label('Added By'),
                            ])
                            ->columns(3)
                            ->visible(fn($record) => $record->disbursement_type != null),
                    ]),

                Section::make('Disbursement Method')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('disbursement_type')
                            ->label('Disbursement Type')
                            ->badge()
                            ->placeholder('Not yet set'),

                        TextEntry::make('requesting_amount')
                            ->label('Amount')
                            ->money('PHP'),

                        TextEntry::make('check_branch_name')
                            ->label('Check Branch Name')
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::CHECK->value)
                            ->placeholder('-'),

                        TextEntry::make('check_no')
                            ->label('Check No.')
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::CHECK->value)
                            ->placeholder('-'),

                        TextEntry::make('voucher_no')
                            ->label('Voucher No.')
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::CHECK->value)
                            ->placeholder('-'),

                        TextEntry::make('cut_off_date')
                            ->label('Cut-off Date')
                            ->date()
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::PAYROLL->value)
                            ->placeholder('-'),

                        TextEntry::make('payroll_credit')
                            ->label('Payroll Credit')
                            ->money('PHP')
                            ->visible(fn($record) => $record->disbursement_type === DisbursementType::PAYROLL->value)
                            ->placeholder('-'),

                        TextEntry::make('disbursementAddedBy.name')
                            ->label('Added By'),
                    ])
                    ->columns(3)
                    ->visible(fn($record) => $record->disbursement_type != null),
            ]);
    }

    /**
     * Determine if the cash request is eligible for payment processing actions.
     *
     * @param mixed $record
     * @return bool
     */
    private function getStatus(mixed $record): bool
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
        $user = Auth::user();
        $status_remarks = app(StatusRemarkResolver::class)->approveByPermissions($user, 'treasury');
        $releasingDate = $data['releasing_date'] ?? $data['payroll_date'] ?? null;
        $timeFrom = $data['releasing_time_from'] ?? null;
        $timeTo = $data['releasing_time_to'] ?? null;

        // Insert the "For Releasing" Data
        ForCashRelease::create([
            'cash_request_id' => $record->id,
            'processed_by' => $user->id,
            'remarks' => $data['remarks'],
            'releasing_date' => $releasingDate,
            'releasing_time_from' => $timeFrom,
            'releasing_time_to' => $timeTo,
            'date_processed' => Carbon::now(),
        ]);

        // If the nature of request is "PETTY CASH", the due date will be 3 days after the releasing date. Else, return null (for the mean time).
        $due_date = $record->nature_of_request == NatureOfRequestEnum::PETTY_CASH->value && $releasingDate
            ? Carbon::parse($releasingDate)->addDays(3)
            : null;

        // Update the record status
        $record->update([
            'status' => Status::APPROVED->value,
            'status_remarks' => $status_remarks,
            'due_date' => $due_date,
        ]);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => Status::IN_PROGRESS->value,
                'new_status' => Status::APPROVED->value,
                'status_remarks' => $status_remarks,
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
     * Reject the cash request, log the rejection, and dispatch notification.
     *
     * @param mixed $record
     * @param array<string, mixed> $data
     */
    private function rejectCashRequest($record, array $data)
    {
        $user = Auth::user();
        $status_remarks = app(StatusRemarkResolver::class)->rejectByPermissions($user, 'treasury');

        // Update the record status and save rejection reason
        $record->update([
            'status' => Status::REJECTED->value,
            'status_remarks' => $status_remarks,
            'reason_for_rejection' => $data['rejection_reason'],
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('rejected')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => Status::PENDING->value,
                'new_status' => Status::REJECTED->value,
                'status_remarks' => $status_remarks,
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

    private function getApproveFormSchema($record): array
    {
        if (
            $record->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value
            && $record->disbursement_type === DisbursementType::PAYROLL->value
        ) {
            return $this->getPayrollApproveFormSchema();
        }

        return $this->getStandardApproveFormSchema();
    }

    /**
     * Build the standard approval form schema for releasing cash requests.
     */
    private function getStandardApproveFormSchema(): array
    {
        return [
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
        ];
    }

    /**
     * Build the payroll-specific approval form schema.
     */
    private function getPayrollApproveFormSchema(): array
    {
        return [
            Textarea::make('remarks')
                ->required(),

            DatePicker::make('payroll_date')
                ->label('Payroll Date')
                ->required()
                ->default(now())
                ->minDate(now()->toDateString()),
        ];
    }

    /**
     * Build the disbursement type selection form schema with conditional fields.
     */
    private function getDisbursementTypeFormSchema(): array
    {
        return array_merge(
            [
                Select::make('disbursement_type')
                    ->label('Disbursement Type')
                    ->options(DisbursementType::filamentOptions())
                    ->required()
                    ->rules('required')
                    ->live(),

                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->readonly()
                    ->default(fn($record) => $record->requesting_amount),
            ],
            $this->getCheckDisbursementTypeSchema(),
            $this->getPayrollDisbursementTypeSchema()
        );
    }

    /**
     * Build the check-specific disbursement fields.
     */
    private function getCheckDisbursementTypeSchema(): array
    {
        return [
            TextInput::make('check_branch_name')
                ->label('Check Branch Name')
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),

            TextInput::make('check_no')
                ->label('Check No.')
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),

            TextInput::make('voucher_no')
                ->label('Voucher No.')
                ->default(fn($record) => $record->voucher_no)
                ->readonly()
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::CHECK->value),
        ];
    }

    /**
     * Build the payroll-specific disbursement fields.
     */
    private function getPayrollDisbursementTypeSchema(): array
    {
        return [
            DatePicker::make('cut_off_date')
                ->label('Cut-off Date')
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value),

            TextInput::make('payroll_credit')
                ->label('Payroll Credit')
                ->visible(fn(Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value)
                ->required(fn(Get $get) => $get('disbursement_type') === DisbursementType::PAYROLL->value),
        ];
    }

    /**
     * Persist the selected disbursement type and related details.
     */
    private function saveDisbursementType($record, array $data): void
    {
        $basePayload = [
            'disbursement_type' => $data['disbursement_type'],
            'disbursement_added_by' => Auth::id(),
        ];

        $typePayload = match ($data['disbursement_type']) {
            DisbursementType::CHECK->value => $this->getCheckDisbursementPayload($data),
            DisbursementType::PAYROLL->value => $this->getPayrollDisbursementPayload($data),
            default => [],
        };

        $record->update(array_merge($basePayload, $typePayload));

        Notification::make()
            ->title('Disbursement details saved.')
            ->success()
            ->send();
    }

    /**
     * Build the payload for check disbursement fields.
     */
    private function getCheckDisbursementPayload(array $data): array
    {
        return [
            'check_branch_name' => $data['check_branch_name'] ?? null,
            'check_no' => $data['check_no'] ?? null,
        ];
    }

    /**
     * Build the payload for payroll disbursement fields.
     */
    private function getPayrollDisbursementPayload(array $data): array
    {
        return [
            'cut_off_date' => $data['cut_off_date'],
            'payroll_credit' => $data['payroll_credit'],
        ];
    }

    private function overrideRequest($record)
    {
        $user = Auth::user();

        // Update the record status and save rejection reason
        $record->update([
            'is_override' => true,
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('override')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => Status::PENDING->value,
                'new_status' => Status::IN_PROGRESS->value,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Cash request {$record->request_no} was override by {$user->name} ({$user->position})");

        // Notify the Treasury Manager once the Treasury Staff override the request.
        ViewPaymentProcess::notifyTreasuryManager(
            $record,
            'Cash Request Overridden',
            "Cash request {$record->request_no} has been overridden."
        );

        return Notification::make()
            ->title('Cash Request Override!')
            ->success()
            ->send();
    }

    private function isTreasuryManager(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles()
            ->where('name', 'treasury_manager')
            ->exists();
    }

    /**
     * Notify treasury manager about payment processing updates.
     * @param $record
     * @param string $title
     * @param string $body
     */
    private static function notifyTreasuryManager($record, string $title, string $body): void
    {
        $treasuryManagers = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'treasury_manager');
            })
            ->get();

        if ($treasuryManagers->isEmpty()) {
            return;
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.payment-processing.view', ['record' => $record->id])),
            ])
            ->sendToDatabase($treasuryManagers);
    }
}
