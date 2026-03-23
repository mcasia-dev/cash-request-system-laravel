<?php

namespace App\Filament\Resources\ReimbursementResource\Pages;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\ReimbursementResource;
use App\Filament\Support\RendersAttachmentPreview;
use App\Filament\Support\RendersDiscussionChat;
use Facades\App\Services\Reimbursement\ForApprovalReimbursementService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewReimbursement extends ViewRecord
{
    use RendersAttachmentPreview, RendersDiscussionChat;

    protected static string $resource = ReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('respond_to_clarification')
                ->label('Respond to Clarification')
                ->color('info')
                ->visible(fn($record) => $this->canRespond($record))
                ->form([
                    Textarea::make('remarks')
                        ->label('Response')
                        ->required()
                        ->rows(4),
                ])
                ->action(fn($record, array $data) => ForApprovalReimbursementService::respondToClarification($record, $data)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Reimbursement Details')
                    ->schema([
                        TextEntry::make('reimbursement_no')
                            ->label('Reimbursement No.'),

                        TextEntry::make('reimbursement_date')
                            ->label('Date')
                            ->date(),

                        TextEntry::make('payee.name')
                            ->label('Payee'),

                        TextEntry::make('reimbursementMode.name')
                            ->label('Mode of Request')
                            ->badge(),

                        TextEntry::make('mode_of_transfer')
                            ->label('Mode of Transfer')
                            ->badge(),

                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('PHP'),

                        TextEntry::make('status')
                            ->badge(),

                        TextEntry::make('status_remarks')
                            ->label('Status Remarks')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                Status::PENDING->value => 'warning',
                                Status::IN_PROGRESS->value => 'secondary',
                                Status::REJECTED->value => 'danger',
                                Status::APPROVED->value => 'success',
                                Status::RELEASED->value => 'info',
                                default => 'secondary',
                            }),

                        TextEntry::make('reason_for_rejection')
                            ->label('Reason for Rejection')
                            ->visible(fn($record) => filled($record->reason_for_rejection)),

                        TextEntry::make('purpose')
                            ->label('Purpose')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Items to Reimburse')
                    ->schema([
                        RepeatableEntry::make('reimbursementItems')
                            ->label('')
                            ->schema([
                                TextEntry::make('item_name')
                                    ->label('Item'),

                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('PHP'),

                                TextEntry::make('description')
                                    ->label('Description')
                                    ->columnSpanFull(),

                                TextEntry::make('attachment')
                                    ->label('Attachment')
                                    ->state(fn($record) => $this->renderAttachmentsHtml($record))
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
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

    private function canRespond($record): bool
    {
        $userId = Auth::id();

        if (!$userId || (int)$record->payee_id !== (int)$userId) {
            return false;
        }

        $status = $record->status instanceof Status ? $record->status->value : (string)$record->status;

        if (!in_array($status, [Status::PENDING->value, Status::IN_PROGRESS->value], true)) {
            return false;
        }

        return $record->discussions()->where('type', 'return')->exists();
    }
}
