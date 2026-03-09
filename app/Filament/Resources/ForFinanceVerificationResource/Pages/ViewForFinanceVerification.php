<?php
namespace App\Filament\Resources\ForFinanceVerificationResource\Pages;

use App\Filament\Resources\ForFinanceVerificationResource;
use Facades\App\Services\CashRequest\ForFinanceVerificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Alignment;

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
                        ->label('AP Voucher No.')
                        ->required(),
                ])
                ->action(fn($record, array $data) => ForFinanceVerificationService::approveRequest($record, $data))
                ->color('primary')
                ->visible(fn($record) => ForFinanceVerificationService::getStatus($record)),

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
                ->action(fn($record, array $data) => ForFinanceVerificationService::rejectRequest($record, $data))
                ->visible(fn($record) => ForFinanceVerificationService::getStatus($record)),
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
                    ->schema([
                        RepeatableEntry::make('activityLists')
                            ->label('')
                            ->getStateUsing(fn($record) => $record->activityLists()
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
                                        ->form([
                                            Textarea::make('rejection_remarks')
                                                ->label('Rejection Remarks')
                                                ->required()
                                                ->maxLength(65535),
                                        ])
                                        ->visible(fn($record): bool => ForFinanceVerificationService::getStatus($this->record) && $record->status !== 'rejected')
                                        ->action(fn(array $data, $record) => ForFinanceVerificationService::rejectActivity($record, $data)),
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

                                SpatieMediaLibraryImageEntry::make('attachment')
                                    ->label('Attached File/Image')
                                    ->collection('attachments')
                                    ->columnSpanFull(),

                                TextEntry::make('status')
                                    ->label('Activity Status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'rejected' => 'danger',
                                        'pending'  => 'warning',
                                        default    => 'gray',
                                    }),

                                TextEntry::make('rejection_remarks')
                                    ->label('Rejection Remarks')
                                    ->visible(fn($record) => filled($record->rejection_remarks))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
