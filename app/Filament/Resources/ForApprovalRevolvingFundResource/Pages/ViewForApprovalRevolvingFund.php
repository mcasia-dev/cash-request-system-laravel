<?php

namespace App\Filament\Resources\ForApprovalRevolvingFundResource\Pages;

use App\Enums\RevolvingFund\Status;
use App\Filament\Resources\ForApprovalRevolvingFundResource;
use App\Filament\Support\RendersDiscussionChat;
use App\Services\RevolvingFund\RevolvingFundApprovalFlowService;
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
                ->visible(fn($record) => $this->canCurrentUserReview($record) && $this->canApproveCurrentStep($record))
                ->requiresConfirmation()
                ->form(fn($record) => $this->buildDynamicStepForm($record))
                ->action(fn($record, array $data) => ForApprovalRevolvingFundService::approve($record, $data)),

            Action::make('Reject')
                ->visible(fn($record) => $this->canCurrentUserReview($record) && $this->canRejectCurrentStep($record))
                ->color('secondary')
                ->requiresConfirmation()
                ->form(fn($record) => $this->buildDynamicStepForm($record))
                ->action(fn($record, array $data) => ForApprovalRevolvingFundService::reject($record, $data)),

            Action::make('Return')
                ->visible(fn($record) => $this->canCurrentUserReview($record))
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

    private function canCurrentUserReview($record): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if (!in_array($record->status, [Status::PENDING->value, Status::IN_PROGRESS->value], true)) {
            return false;
        }

        return app(RevolvingFundApprovalFlowService::class)->userCanReview($record, $user);
    }

    private function canApproveCurrentStep($record): bool
    {
        $config = $this->getCurrentStepConfig($record);

        return (bool) ($config['can_approve'] ?? true);
    }

    private function canRejectCurrentStep($record): bool
    {
        $config = $this->getCurrentStepConfig($record);

        return (bool) ($config['can_reject'] ?? true);
    }

    private function buildDynamicStepForm($record): array
    {
        $config = $this->getCurrentStepConfig($record);
        $schema = (array) ($config['form_schema'] ?? []);
        $fields = [];

        foreach ($schema as $item) {
            $key = (string) ($item['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $label = (string) ($item['label'] ?? $key);
            $type = (string) ($item['type'] ?? 'text');
            $required = (bool) ($item['required'] ?? false);
            $fieldPath = "step_form_data.{$key}";

            $field = match ($type) {
                'number' => TextInput::make($fieldPath)->numeric(),
                'textarea' => Textarea::make($fieldPath)->rows(3),
                'date' => DatePicker::make($fieldPath),
                default => TextInput::make($fieldPath),
            };

            $field->label($label);

            if ($required) {
                $field->required();
            }

            $fields[] = $field;
        }

        return $fields;
    }

    private function getCurrentStepConfig($record): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return ForApprovalRevolvingFundService::getCurrentStepConfiguration($record, $user) ?? [];
    }
}
