<?php

namespace App\Filament\Resources\ForAccountingVerificationResource\Pages;

use App\Filament\Resources\ForAccountingVerificationResource;
use App\Filament\Support\RendersAttachmentPreview;
use Facades\App\Services\Reimbursement\ForAccountingVerificationService;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewForAccountingVerification extends ViewRecord
{
    use RendersAttachmentPreview;

    protected static string $resource = ForAccountingVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Override')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn($record) => ForAccountingVerificationService::canOverride($record))
                ->action(fn($record) => ForAccountingVerificationService::override($record)),

            Action::make('Manager Approve')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn($record) => ForAccountingVerificationService::canManagerApprove($record))
                ->action(fn($record) => ForAccountingVerificationService::managerApprove($record)),

            Action::make('Final Approve')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn($record) => ForAccountingVerificationService::canFinalApprove($record))
                ->action(fn($record) => ForAccountingVerificationService::finalApprove($record)),
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

                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('PHP'),

                        TextEntry::make('status')
                            ->badge(),

                        TextEntry::make('status_remarks')
                            ->label('Status Remarks')
                            ->badge(),

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
            ]);
    }
}

