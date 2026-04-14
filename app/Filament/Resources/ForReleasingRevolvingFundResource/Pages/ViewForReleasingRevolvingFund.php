<?php

namespace App\Filament\Resources\ForReleasingRevolvingFundResource\Pages;

use App\Filament\Resources\ForReleasingRevolvingFundResource;
use Facades\App\Services\RevolvingFund\ForReleasingRevolvingFundService;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewForReleasingRevolvingFund extends ViewRecord
{
    protected static string $resource = ForReleasingRevolvingFundResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Action::make('release')
                ->label('Release')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn($record) => ForReleasingRevolvingFundService::canRelease($record))
                ->action(fn($record) => ForReleasingRevolvingFundService::release($record)),
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

                        TextEntry::make('modeOfTransfer.name')
                            ->label('Mode of Transfer')
                            ->badge(),

                        TextEntry::make('initial_amount')
                            ->label('Initial Amount')
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

                Section::make('Field Work Assignment')
                    ->schema([
                        TextEntry::make('field_work_assignment')
                            ->hiddenLabel()
                            ->state(fn($record) => ForReleasingRevolvingFundService::getFieldOfWorkAssignmentState($record))
                            ->html(),
                    ]),

                Section::make('Approval Form Details')
                    ->visible(fn($record) => $record->revolvingFundApprovals()->whereNotNull('step_form_data')->exists())
                    ->schema([
                        TextEntry::make('custom_step_forms')
                            ->hiddenLabel()
                            ->state(fn($record) => ForReleasingRevolvingFundService::renderCustomStepFormsHtml($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
