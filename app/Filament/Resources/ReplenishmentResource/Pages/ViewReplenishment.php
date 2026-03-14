<?php

namespace App\Filament\Resources\ReplenishmentResource\Pages;

use App\Filament\Resources\ReplenishmentResource;
use App\Filament\Support\RendersAttachmentPreview;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewReplenishment extends ViewRecord
{
    use RendersAttachmentPreview;

    protected static string $resource = ReplenishmentResource::class;

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
                            ->label('Initial Amount')
                            ->money('PHP'),
                        TextEntry::make('remaining_amount')
                            ->label('Remaining Amount')
                            ->money('PHP'),
                        TextEntry::make('total_amount')
                            ->label('Total')
                            ->money('PHP'),
                        TextEntry::make('amount_to_return')
                            ->label('Amount to Return')
                            ->money('PHP'),
                        TextEntry::make('amount_to_deduct')
                            ->label('Amount to Deduct')
                            ->money('PHP'),
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('F d, Y h:i A'),
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
            ]);
    }
}
