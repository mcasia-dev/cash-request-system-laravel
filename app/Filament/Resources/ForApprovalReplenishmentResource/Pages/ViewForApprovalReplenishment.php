<?php

namespace App\Filament\Resources\ForApprovalReplenishmentResource\Pages;

use App\Filament\Resources\ForApprovalReplenishmentResource;
use App\Filament\Support\RendersAttachmentPreview;
use App\Filament\Support\RendersDiscussionChat;
use App\Services\RevolvingFund\ForApprovalReplenishmentService;
use App\Services\RevolvingFund\ReplenishmentApprovalFlowService;
use Facades\App\Services\RevolvingFund\ForApprovalReplenishmentService as ForApprovalReplenishmentFacade;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ViewForApprovalReplenishment extends ViewRecord
{
    use RendersAttachmentPreview, RendersDiscussionChat;

    protected static string $resource = ForApprovalReplenishmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit_item_review')
                ->label(fn($record) => $this->getReviewActionLabel($record))
                ->color('success')
                ->visible(fn($record) => $this->canCurrentUserReview($record))
                ->form(fn($record): array => $this->buildReviewFormSchema($record))
                ->modalHeading(fn($record) => $this->getReviewModalHeading($record))
                ->modalSubmitActionLabel(fn($record) => $this->getReviewSubmitLabel($record))
                ->action(fn($record, array $data) => ForApprovalReplenishmentFacade::submitItemReviewAction($record, $data)),

            Action::make('return_for_clarification')
                ->label('Return')
                ->color('warning')
                ->visible(fn($record) => $this->canCurrentUserReview($record))
                ->form([
                    Textarea::make('remarks')
                        ->label('Return Remarks')
                        ->required()
                        ->rows(4),
                ])
                ->action(fn($record, array $data) => ForApprovalReplenishmentFacade::returnForClarification($record, $data)),

            Action::make('replenish_amount')
                ->label('Replenish')
                ->color('primary')
                ->visible(fn($record) => $this->canApplyReplenishmentAmount($record))
                ->form([
                    TextInput::make('initial_amount')
                        ->label('Revolving Fund Amount')
                        ->numeric()
                        ->prefix('PHP')
                        ->default(fn($record) => (float)$record->initial_amount)
                        ->readOnly()
                        ->dehydrated(false),

                    TextInput::make('remaining_amount')
                        ->label('Current Remaining Amount')
                        ->numeric()
                        ->prefix('PHP')
                        ->default(fn($record) => (float)$record->remaining_amount)
                        ->readOnly()
                        ->dehydrated(false),

                    TextInput::make('amount_to_add')
                        ->label('Replenish Amount')
                        ->default(fn($record) => (float)$record->total_amount)
                        ->numeric()
                        ->prefix('PHP')
                        ->minValue(0.01)
                        ->helperText('Any amount above the missing fund balance will be saved as Amount to Reimburse.')
                        ->required(),
                ])
                ->modalHeading('Replenish Amount')
                ->modalSubmitActionLabel('Apply')
                ->action(function ($record, array $data): void {
                    $this->replenishAction($record, $data['amount_to_add']);
                }),
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
                            ->label('Requestor'),

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
                            ->label('Amount to Return/Deduct')
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

                Section::make('Replenishment Items')
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
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('discussion_chat')
                            ->hiddenLabel()
                            ->state(fn($record) => $this->renderDiscussionChatHtml($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private function canCurrentUserReview($record): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return app(ForApprovalReplenishmentService::class)->userCanReview($record, $user);
    }

    private function buildReviewFormSchema($record): array
    {
        $stepConfig = $this->getCurrentStepConfig($record);
        $useItemSelection = (bool)($stepConfig['use_item_selection'] ?? true);
        $canReject = (bool)($stepConfig['can_reject'] ?? true);
        $schema = [];

        if ($useItemSelection) {
            $schema[] = CheckboxList::make('approved_item_ids')
                ->label('Approved Items (Please check the item(s) you want to approve.)')
                ->options(function () use ($record): array {
                    return $record->replenishmentItems
                        ->filter(fn($item) => $item->is_approved !== false)
                        ->mapWithKeys(fn($item) => [
                            $item->id => "{$item->expense_name} (PHP " . number_format((float)$item->amount, 2) . ')',
                        ])
                        ->all();
                })
                ->default(function () use ($record): array {
                    return $record->replenishmentItems
                        ->filter(fn($item) => $item->is_approved === true)
                        ->pluck('id')
                        ->map(fn($id) => (string)$id)
                        ->all();
                })
                ->columns(1)
                ->bulkToggleable()
                ->helperText('Items already marked as not approved are locked and removed from this list.');
        } elseif ($canReject) {
            $schema[] = Toggle::make('reject_request')
                ->label('Reject Request')
                ->inline(false)
                ->default(false)
                ->helperText('Enable this to reject this step without item selection.');
        }

        foreach ($this->buildDynamicStepFormFields($stepConfig) as $field) {
            $schema[] = $field;
        }

        $schema[] = Textarea::make('remarks')
            ->label('Reviewer Remarks')
            ->rows(4)
            ->helperText('Required if no item is approved or if request is rejected.');

        return $schema;
    }

    private function getReviewActionLabel($record): string
    {
        $stepConfig = $this->getCurrentStepConfig($record);

        if (($stepConfig['can_verify'] ?? false) && !($stepConfig['can_approve'] ?? true)) {
            return 'Verify';
        }

        return 'Approve';
    }

    private function getReviewModalHeading($record): string
    {
        return $this->getReviewActionLabel($record) . ' Replenishment Items';
    }

    private function getReviewSubmitLabel($record): string
    {
        return 'Submit';
    }

    private function buildDynamicStepFormFields(array $stepConfig): array
    {
        $fields = [];
        $formSchema = (array)($stepConfig['form_schema'] ?? []);

        foreach ($formSchema as $config) {
            $key = (string)($config['key'] ?? '');
            $label = (string)($config['label'] ?? $key);
            $type = (string)($config['type'] ?? 'text');
            $required = (bool)($config['required'] ?? false);

            if ($key === '') {
                continue;
            }

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

        if (!$user) {
            return [];
        }

        return app(ForApprovalReplenishmentService::class)
            ->getCurrentStepConfiguration($record, $user) ?? [];
    }

    private function canApplyReplenishmentAmount($record): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Only allow after the request is fully approved.
        if ((string)$record->status !== 'approved') {
            return false;
        }

        if (filled($record->replenished_at)) {
            return false;
        }

        $approvals = $record->replenishmentApprovals()
            ->orderByRaw('COALESCE(step_order, id)')
            ->orderBy('id')
            ->get();

        if ($approvals->isEmpty()) {
            return false;
        }

        // Ensure no pending steps remain.
        if ($approvals->contains(fn($approval) => $approval->status === 'pending')) {
            return false;
        }

        // Show only to the final approver who completed the last step.
        $lastAction = $approvals
            ->filter(fn($approval) => in_array($approval->status, ['approved', 'declined'], true))
            ->last();

        if (!$lastAction) {
            return false;
        }

        if ($lastAction->status !== 'approved' || (int)$lastAction->approved_by !== (int)$user->id) {
            return false;
        }

        if ((bool)($lastAction->can_replenish ?? false)) {
            return true;
        }

        $stepConfig = app(ReplenishmentApprovalFlowService::class)
            ->getStepConfiguration($record, $lastAction);

        return (bool)($stepConfig['can_replenish'] ?? false);
    }
}
