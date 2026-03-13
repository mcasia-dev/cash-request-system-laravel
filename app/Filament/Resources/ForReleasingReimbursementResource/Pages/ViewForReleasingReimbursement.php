<?php

namespace App\Filament\Resources\ForReleasingReimbursementResource\Pages;

use App\Filament\Resources\ForReleasingReimbursementResource;
use App\Filament\Support\RendersAttachmentPreview;
use Facades\App\Services\Reimbursement\ForReleasingReimbursementService;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewForReleasingReimbursement extends ViewRecord
{
    use RendersAttachmentPreview;

    protected static string $resource = ForReleasingReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('release')
                ->label('Release')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn($record) => ForReleasingReimbursementService::canRelease($record))
                ->action(fn($record) => ForReleasingReimbursementService::release($record)),
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

                        TextEntry::make('payee.name')
                            ->label('Requestor'),

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
                            ->badge(),

                        TextEntry::make('releasing_date')
                            ->label('Releasing Date')
                            ->date(),

                        TextEntry::make('remarks')
                            ->label('Remarks')
                            ->placeholder('-'),
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
