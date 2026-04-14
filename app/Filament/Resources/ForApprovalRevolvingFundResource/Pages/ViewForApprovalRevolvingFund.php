<?php

namespace App\Filament\Resources\ForApprovalRevolvingFundResource\Pages;

use App\Enums\RevolvingFund\Status;
use App\Filament\Resources\ForApprovalRevolvingFundResource;
use App\Filament\Support\RendersDiscussionChat;
use App\Services\RevolvingFund\RevolvingFundApprovalFlowService;
use Facades\App\Services\RevolvingFund\RevolvingFundService;
use Facades\App\Services\RevolvingFund\ForApprovalRevolvingFundService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewForApprovalRevolvingFund extends ViewRecord
{
    use RendersDiscussionChat;

    protected static string $resource = ForApprovalRevolvingFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Approve')
                ->visible(fn($record) => ForApprovalRevolvingFundService::canCurrentUserReview($record) && ForApprovalRevolvingFundService::canApproveCurrentStep($record))
                ->requiresConfirmation()
                ->form(fn($record) => ForApprovalRevolvingFundService::buildDynamicStepForm($record))
                ->action(fn($record, array $data) => ForApprovalRevolvingFundService::approve($record, $data)),

            Action::make('Reject')
                ->visible(fn($record) => ForApprovalRevolvingFundService::canCurrentUserReview($record) && ForApprovalRevolvingFundService::canRejectCurrentStep($record))
                ->color('secondary')
                ->requiresConfirmation()
                ->form(fn($record) => ForApprovalRevolvingFundService::buildDynamicStepForm($record))
                ->action(fn($record, array $data) => ForApprovalRevolvingFundService::reject($record, $data)),

            Action::make('Return')
                ->visible(fn($record) => ForApprovalRevolvingFundService::canCurrentUserReview($record))
                ->color('warning')
                ->form([
                    Textarea::make('remarks')
                        ->label('Return Remarks')
                        ->required()
                        ->rows(4),
                ])
                ->action(fn($record, array $data) => ForApprovalRevolvingFundService::returnForClarification($record, $data)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Revolving Fund Details')
                    ->schema([
                        TextEntry::make('fund_code')
                            ->label('Fund Code'),

                        TextEntry::make('addedBy.name')
                            ->label('Requestor'),

                        TextEntry::make('user.name')
                            ->label('Recipient')
                            ->formatStateUsing(fn($record) => RevolvingFundService::getFormattedName($record)),

                        TextEntry::make('initial_amount')
                            ->label('Initial Amount')
                            ->money('PHP'),

                        TextEntry::make('remaining_amount')
                            ->label('Remaining Amount')
                            ->money('PHP'),

                        TextEntry::make('area_of_assignment')
                            ->label('Area of Assignment'),

                        TextEntry::make('purposes.purpose')
                            ->label('Purposes')
                            ->badge()
                            ->columnSpan(2),

                        TextEntry::make('other_purpose')
                            ->label('Other Purpose')
                            ->visible(fn($record) => filled($record->other_purpose)),

                        TextEntry::make('field_work_assignment')
                            ->label('Field Work Assignment')
                            ->state(fn($record) => $this->getFieldOfWorkState($record))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                Status::PENDING->value => 'warning',
                                Status::IN_PROGRESS->value => 'secondary',
                                Status::APPROVED->value => 'success',
                                Status::REJECTED->value => 'danger',
                                Status::REPLENISHED->value => 'info',
                                default => 'secondary',
                            }),

                        TextEntry::make('status_remarks')
                            ->label('Status Remarks')
                            ->badge(),

                        TextEntry::make('created_at')
                            ->label('Date Submitted')
                            ->dateTime('F d, Y h:i A'),
                    ])
                    ->columns(3),

                Section::make('Clarifications / Returns')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('discussion_chat')
                            ->hiddenLabel()
                            ->state(fn($record) => $this->renderDiscussionChatHtml($record))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    /**
     * @param $record
     * @return string
     */
    function getFieldOfWorkState($record): string
    {
        return collect($record->field_work_assignment ?? [])
            ->map(function ($item) {
                $day = isset($item['day']) ? ucfirst($item['day']) : 'Day';
                $from = $item['time_from'] ?? '-';
                $to = $item['time_to'] ?? '-';

                return "{$day}: {$from} - {$to}";
            })
            ->join('<br>');
    }
}
