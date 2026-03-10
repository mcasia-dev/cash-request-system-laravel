<?php

namespace App\Filament\Resources\ForCashReleaseResource\Pages;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\ForCashReleaseResource;
use App\Models\CashRequest;
use App\Models\ForCashRelease;
use Carbon\Carbon;
use Facades\App\Services\CashRequest\ForCashReleaseService;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Alignment;
use Njxqlus\Filament\Components\Infolists\LightboxSpatieMediaLibraryImageEntry;

class ViewForCashRelease extends ViewRecord
{
    protected static string $resource = ForCashReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CHANGE RELEASING DATE BUTTON
            Action::make('changeReleasingDate')
                ->requiresConfirmation()
                ->form(fn($record) => $this->getChangeReleasingDateFormSchema())
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn(ForCashRelease $record, array $data) => ForCashReleaseService::changeReleasingDate($record, $data))
                ->color('warning')
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn($record) => ForCashReleaseService::getStatus($record))
                ->disabled(fn($record) => (int)($record->update_releasing_date_attempt ?? 0) >= 3),

            // APPROVED BUTTON
            Action::make('Release')
                ->requiresConfirmation()
                ->form(fn($record) => [
                    Textarea::make('remarks')
                        ->required(),
                ])
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn(ForCashRelease $record, array $data) => ForCashReleaseService::releaseCashRequest($record, $data))
                ->color('primary')
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn($record) => ForCashReleaseService::getStatus($record)),

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
                ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ]))
                ->action(fn(CashRequest $record, array $data) => ForCashReleaseService::rejectCashRequest($record, $data))
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                ])
                ->visible(fn($record) => ForCashReleaseService::getStatus($record)),
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

                        TextEntry::make('cashRequest.nature_of_request')
                            ->label('Nature of Request')
                            ->badge(),

                        TextEntry::make('cashRequest.requesting_amount')
                            ->label('Total Requesting Amount')
                            ->money('PHP'),

                        TextEntry::make('cashRequest.status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'released' => 'info',
                                'liquidated' => 'primary',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('cashRequest.reason_for_rejection')
                            ->label('Reason for Rejection')
                            ->visible(fn($record) => $record->cashRequest?->status === Status::REJECTED->value),

                        TextEntry::make('cashRequest.reason_for_cancelling')
                            ->label('Reason for Cancelling')
                            ->visible(fn($record) => $record->cashRequest?->status === Status::CANCELLED->value),
                    ])
                    ->columns(3),

                Section::make('Activity Information')
                    ->collapsible()
                    ->getStateUsing(fn($record) => $record->activityLists()
                        ->where('status', '!=', 'rejected')
                        ->get())
                    ->schema([
                        RepeatableEntry::make('cashRequest.activityLists')
                            ->label('')
                            ->getStateUsing(fn($record) => $record->cashRequest->activityLists()
                                ->where('status', '!=', 'rejected')
                                ->get())
                            ->schema([
                                Actions::make([
                                    InfolistAction::make('rejectActivity')
                                        ->icon('heroicon-o-minus')
                                        ->iconButton()
                                        ->tooltip('Reject activity')
                                        ->color('danger')
                                        ->size('xs')
                                        ->extraAttributes([
                                            'class' => 'border border-red-500 rounded-full text-transparent hover:bg-red-50',
                                        ])
                                        ->modalHeading('Reject Activity')
                                        ->modalDescription('Are you sure you want to reject this activity?')
                                        ->modalSubmitActionLabel('Reject')
                                        ->modalSubmitAction(fn(StaticAction $action) => $action->extraAttributes([
                                            'wire:loading.attr' => 'disabled',
                                        ]))
                                        ->form([
                                            Textarea::make('rejection_remarks')
                                                ->label('Rejection Remarks')
                                                ->required()
                                                ->maxLength(65535),
                                        ])
                                        ->visible(fn($record): bool => ForCashReleaseService::getStatus($this->record) && $record->status !== 'rejected')
                                        ->action(fn(array $data, $record) => ForCashReleaseService::rejectActivity($record, $data)),
                                ])
                                    ->alignment(Alignment::End)
                                    ->fullWidth()
                                    ->columnSpanFull(),

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

                                LightboxSpatieMediaLibraryImageEntry::make('attachment')
                                    ->label('Attached File/Images')
                                    ->collection('attachments')
                                    ->columnSpanFull(),

                                TextEntry::make('status')
                                    ->label('Activity Status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'rejected' => 'danger',
                                        'pending' => 'warning',
                                        default => 'gray',
                                    }),

                                TextEntry::make('rejection_remarks')
                                    ->label('Rejection Remarks')
                                    ->visible(fn($record) => filled($record->rejection_remarks))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Disbursement Method')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('cashRequest.disbursement_type')
                            ->label('Disbursement Type')
                            ->badge()
                            ->placeholder('Not yet set'),

                        TextEntry::make('cashRequest.requesting_amount')
                            ->label('Amount')
                            ->money('PHP'),

                        TextEntry::make('cashRequest.check_branch_name')
                            ->label('Check Branch Name')
                            ->visible(fn($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('cashRequest.check_no')
                            ->label('Check No.')
                            ->visible(fn($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('cashRequest.voucher_no')
                            ->label('Voucher No.')
                            ->visible(fn($record) => $record->isCheckDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('cashRequest.cut_off_date')
                            ->label('Cut-off Date')
                            ->date()
                            ->visible(fn($record) => $record->isPayrollDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('cashRequest.payroll_credit')
                            ->label('Payroll Credit')
                            ->money('PHP')
                            ->visible(fn($record) => $record->isPayrollDisbursement())
                            ->placeholder('-'),

                        TextEntry::make('cashRequest.disbursementAddedBy.name')
                            ->label('Added By'),
                    ])
                    ->columns(3)
                    ->visible(fn($record) => $record->cashRequest->disbursement_type != null),

                Section::make('Dates')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('releasing_date')
                            ->label('Releasing Date')
                            ->formatStateUsing(function ($record) {
                                return "{$record?->releasing_date?->format('F d, Y')} "
                                    . Carbon::parse($record?->releasing_time_from)?->format('h:i A')
                                    . ' - '
                                    . Carbon::parse($record?->releasing_time_to)?->format('h:i A');
                            }),

                        TextEntry::make('cashRequest.due_date')
                            ->label('Liquidation Due Date')
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

    private function getChangeReleasingDateFormSchema(): array
    {
        return [
            Textarea::make('remarks')
                ->required(),

            DatePicker::make('releasing_date')
                ->label('Releasing Date')
                ->required()
                ->default(fn($record) => $record->releasing_date ?? now())
                ->minDate(now()->toDateString()),

            TimePicker::make('releasing_time_from')
                ->label('Releasing Time From')
                ->required()
                ->default(fn($record) => $record->releasing_time_from ?? now()),

            TimePicker::make('releasing_time_to')
                ->label('Releasing Time To')
                ->required()
                ->default(fn($record) => $record->releasing_time_to ?? now()),
        ];
    }
}
