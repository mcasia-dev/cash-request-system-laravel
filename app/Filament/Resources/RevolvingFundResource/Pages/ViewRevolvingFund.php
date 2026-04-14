<?php

namespace App\Filament\Resources\RevolvingFundResource\Pages;

use App\Enums\RevolvingFund\Status;
use App\Filament\Resources\RevolvingFundResource;
use App\Filament\Support\RendersDiscussionChat;
use Facades\App\Services\RevolvingFund\RevolvingFundService;
use Facades\App\Services\RevolvingFund\ForApprovalRevolvingFundService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewRevolvingFund extends ViewRecord
{
    use RendersDiscussionChat;

    protected static string $resource = RevolvingFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('review_replenishment_request')
                ->label('Review Replenishment Request')
                ->color('success')
                ->visible(fn($record) => RevolvingFundService::canDepartmentHeadReviewReplenishment($record))
                ->url(fn($record) => $this->getReviewReplenishmentUrl($record)),

            Action::make('tracking')
                ->label('Tracking Status')
                ->color('warning')
                ->url(fn($record) => $this->getTrackingStatusUrl($record)),

            Action::make('Respond')
                ->label('Respond to Clarification')
                ->color('info')
                ->visible(fn($record) => RevolvingFundService::canRespond($record))
                ->form([
                    Textarea::make('remarks')
                        ->label('Response')
                        ->required()
                        ->rows(4),
                ])
                ->action(fn($record, array $data) => ForApprovalRevolvingFundService::respondToClarification($record, $data)),
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
                            ->state(fn($record) => $this->fieldWorkAssignmentState($record))
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
                                Status::RELEASED->value => 'info',
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

                Section::make('Replenishment History')
                    ->visible(fn($record) => $record->replenishments()->exists())
                    ->schema([
                        TextEntry::make('replenishment_history')
                            ->hiddenLabel()
                            ->state(fn($record) => RevolvingFundService::renderReplenishmentHistoryTable($record))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Clarifications / Returns')
                    ->visible(fn($record) => $this->canViewDiscussionChat($record))
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

    private function getReviewReplenishmentUrl($record): string
    {
        $replenishment = $record->replenishments()
            ->whereIn('status', ['pending', 'returned'])
            ->latest('id')
            ->first();

        if (!$replenishment) {
            return '#';
        }

        return route('filament.admin.resources.for-approval-replenishments.view', ['record' => $replenishment->id]);
    }

    private function getTrackingStatusUrl($record): string
    {
        return route('filament.admin.resources.revolving-funds.tracking', ['record' => $record]);
    }

    private function fieldWorkAssignmentState($record)
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
