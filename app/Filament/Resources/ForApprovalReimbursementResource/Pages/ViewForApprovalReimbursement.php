<?php

namespace App\Filament\Resources\ForApprovalReimbursementResource\Pages;

use App\Enums\CashRequest\Status;
use App\Filament\Resources\ForApprovalReimbursementResource;
use App\Filament\Support\RendersAttachmentPreview;
use Facades\App\Services\Reimbursement\ForApprovalReimbursementService;
use App\Services\Reimbursement\ReimbursementApprovalFlowService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewForApprovalReimbursement extends ViewRecord
{
    use RendersAttachmentPreview;

    protected static string $resource = ForApprovalReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Approve')
                ->visible(fn($record) => ($record->status === Status::PENDING->value || $record->status === Status::IN_PROGRESS->value) && $this->canCurrentUserReview($record))
                ->requiresConfirmation()
                ->action(fn($record) => ForApprovalReimbursementService::approve($record)),

            Action::make('Reject')
                ->visible(fn($record) => ($record->status === Status::PENDING->value || $record->status === Status::IN_PROGRESS->value) && $this->canCurrentUserReview($record))
                ->color('secondary')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(65535),
                ])
                ->modalHeading('Reject Reimbursement')
                ->modalSubmitActionLabel('Reject')
                ->action(fn($record, array $data) => ForApprovalReimbursementService::reject($record, $data)),
        ];
    }

    private function canCurrentUserReview($record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return app(ReimbursementApprovalFlowService::class)->userCanReview($record, $user);
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

                        TextEntry::make('purpose')
                            ->label('Purpose')
                            ->columnSpanFull(),

                        TextEntry::make('reason_for_rejection')
                            ->label('Reason for Rejection')
                            ->visible(fn($record) => filled($record->reason_for_rejection))
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
            ]);
    }
}
