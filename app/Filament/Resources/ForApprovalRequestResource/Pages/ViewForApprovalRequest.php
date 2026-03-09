<?php
namespace App\Filament\Resources\ForApprovalRequestResource\Pages;

use App\Filament\Resources\ForApprovalRequestResource;
use App\Services\CashRequestApprovalFlowService;
use Facades\App\Services\CashRequest\ForApprovalRequestService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Auth;

class ViewForApprovalRequest extends ViewRecord
{
    protected static string $resource = ForApprovalRequestResource::class;

    /**
     * Define the header actions for approving or rejecting a request.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('Approve')
                ->visible($this->isVisible())
                ->requiresConfirmation()
                ->action(fn($record) => ForApprovalRequestService::approveForApprovalRequest($record)),

            Action::make('Reject')
                ->visible($this->isVisible())
                ->color('secondary')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Cash Request')
                ->modalSubmitActionLabel('Reject')
                ->action(fn($record, array $data) => ForApprovalRequestService::rejectForApprovalRequest($record, $data)),
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

                        TextEntry::make('status_remarks')
                            ->badge(),
                    ])
                    ->columns(3),

                Section::make('Activity Information')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('activityLists')
                            ->label('')
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
                                        ->visible(fn($record) => ($this->isVisible())($this->record) && $record->status !== 'rejected')
                                        ->action(fn(array $data, $record) => ForApprovalRequestService::getRejectActivityAction($data, $record)),
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

    /**
     * Determine if the approve/reject actions should be visible for the current user.
     * @return \Closure
     */
    public function isVisible(): \Closure
    {
        return function ($record): bool {
            $user = Auth::user();

            if (! $user) {
                return false;
            }

            return app(CashRequestApprovalFlowService::class)->userCanReview($record, $user);
        };
    }
}
