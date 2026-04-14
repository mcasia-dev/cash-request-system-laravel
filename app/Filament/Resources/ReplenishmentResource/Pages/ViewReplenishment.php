<?php

namespace App\Filament\Resources\ReplenishmentResource\Pages;

use App\Filament\Resources\ReplenishmentResource;
use App\Filament\Support\RendersAttachmentPreview;
use App\Filament\Support\RendersDiscussionChat;
use Facades\App\Services\Replenishment\ReplenishmentService;
use Facades\App\Services\RevolvingFund\ForApprovalReplenishmentService;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Textarea;

class ViewReplenishment extends ViewRecord
{
    use RendersAttachmentPreview, RendersDiscussionChat;

    protected static string $resource = ReplenishmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('respond_to_clarification')
                ->label('Respond to Clarification')
                ->color('info')
                ->visible(fn($record) => ReplenishmentService::canRespond($record))
                ->form([
                    Textarea::make('remarks')
                        ->label('Response')
                        ->required()
                        ->rows(4),
                ])
                ->action(fn($record, array $data) => ForApprovalReplenishmentService::respondToClarification($record, $data)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Replenishment Details')
                    ->schema([
                        TextEntry::make('revolvingFund.fund_code')
                            ->label('Fund Code'),

                        TextEntry::make('revolvingFund.user.name')
                            ->label('Fund User'),

                        TextEntry::make('initial_amount')
                            ->label('Revolving Fund Amount')
                            ->money('PHP'),

                        TextEntry::make('old_remaining_amount')
                            ->label('Old Remaining Amount')
                            ->money('PHP')
                            ->placeholder('-')
                            ->visible(fn($state) => !is_null($state)),

                        TextEntry::make('total_amount')
                            ->label('Replenish Total Amount')
                            ->money('PHP'),

                        TextEntry::make('remaining_amount')
                            ->label('Remaining Amount')
                            ->money('PHP'),

                        TextEntry::make('amount_to_return')
                            ->label('Amount to Return / Deduct')
                            ->color(fn($state) => $state > 0 ? 'danger' : null)
                            ->money('PHP'),

                        TextEntry::make('amount_to_reimburse')
                            ->label('Amount to Reimburse')
                            ->money('PHP')
                            ->color(fn($state) => $state > 0 || !is_null($state) ? 'success' : null)
                            ->visible(fn($state) => !is_null($state)),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'warning',
                                'returned' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'replenished' => 'primary',
                                default => 'secondary',
                            }),

                        TextEntry::make('status_remarks')
                            ->label('Status Remarks')
                            ->placeholder('-'),

                        TextEntry::make('replenishedBy.name')
                            ->label('Replenished By')
                            ->placeholder('-')
                            ->visible(fn($state) => !is_null($state)),

                        TextEntry::make('replenished_at')
                            ->label('Replenished At')
                            ->dateTime('F d, Y h:i A')
                            ->placeholder('-')
                            ->visible(fn($state) => !is_null($state)),

                        TextEntry::make('reason_for_rejection')
                            ->label('Rejection Reason')
                            ->visible(fn($record) => filled($record->reason_for_rejection))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Expense Breakdown')
                    ->schema([
                        RepeatableEntry::make('replenishmentItems')
                            ->label('')
                            ->schema([
                                TextEntry::make('expense_name')
                                    ->label('Expense'),

                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('PHP'),

                                TextEntry::make('is_approved')
                                    ->label('Item Review')
                                    ->state(function ($record): string {
                                        if ($record->is_approved === null) {
                                            return 'Pending Review';
                                        }

                                        return $record->is_approved ? 'Approved' : 'Not Approved';
                                    })
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'Approved' => 'success',
                                        'Not Approved' => 'danger',
                                        default => 'warning',
                                    }),

                                TextEntry::make('approval_remarks')
                                    ->label('Reviewer Remarks')
                                    ->placeholder('-'),

                                TextEntry::make('attachment')
                                    ->label('Attachment')
                                    ->state(fn($record) => $this->renderAttachmentsHtml($record))
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Clarifications / Returns')
                    ->schema([
                        TextEntry::make('discussion_chat')
                            ->hiddenLabel()
                            ->state(fn($record) => $this->renderDiscussionChatHtml($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
