<?php

namespace App\Filament\Resources\RevolvingFundResource\Pages;

use App\Enums\RevolvingFund\Status;
use App\Filament\Resources\RevolvingFundResource;
use App\Filament\Support\RendersDiscussionChat;
use Facades\App\Services\RevolvingFund\ForApprovalRevolvingFundService;
use Facades\App\Services\RevolvingFund\ReplenishmentApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

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
                ->visible(fn($record) => $this->canDepartmentHeadReviewReplenishment($record))
                ->url(function ($record): string {
                    $replenishment = $record->replenishments()
                        ->whereIn('status', ['pending', 'returned'])
                        ->latest('id')
                        ->first();

                    if (! $replenishment) {
                        return '#';
                    }

                    return route('filament.admin.resources.for-approval-replenishments.view', ['record' => $replenishment->id]);
                }),

            Action::make('Respond')
                ->label('Respond to Clarification')
                ->color('info')
                ->visible(fn($record) => $this->canRespond($record))
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
                            ->label('Recipient'),
                        TextEntry::make('initial_amount')
                            ->label('Initial Amount')
                            ->money('PHP'),
                        TextEntry::make('remaining_amount')
                            ->label('Remaining Amount')
                            ->money('PHP'),
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

                Section::make('Latest Replenishment Request')
                    ->schema([
                        TextEntry::make('latest_replenishment_status')
                            ->label('Status')
                            ->state(fn($record) => $record->replenishments()->latest('id')->value('status') ?? 'N/A')
                            ->badge(),
                        TextEntry::make('latest_replenishment_remarks')
                            ->label('Status Remarks')
                            ->state(fn($record) => $record->replenishments()->latest('id')->value('status_remarks') ?? '-'),
                        TextEntry::make('latest_replenishment_total')
                            ->label('Total Amount')
                            ->state(fn($record) => $record->replenishments()->latest('id')->value('total_amount'))
                            ->money('PHP'),
                    ])
                    ->columns(3),

                Section::make('Clarifications / Returns')
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

    private function canRespond($record): bool
    {
        $userId = Auth::id();

        if (! $userId || (int) $record->added_by !== (int) $userId) {
            return false;
        }

        if (! in_array($record->status, [Status::PENDING->value, Status::IN_PROGRESS->value], true)) {
            return false;
        }

        return $record->discussions()->where('type', 'return')->exists();
    }

    private function canDepartmentHeadReviewReplenishment($record): bool
    {
        return ReplenishmentApprovalService::canCurrentDepartmentHeadReview($record)
            && ReplenishmentApprovalService::hasActionableReplenishment($record);
    }
}
