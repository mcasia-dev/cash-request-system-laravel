<?php

namespace App\Filament\Resources\ForApprovalReplenishmentResource\Pages;

use App\Filament\Resources\ForApprovalReplenishmentResource;
use App\Filament\Support\RendersAttachmentPreview;
use App\Services\RevolvingFund\ForApprovalReplenishmentService;
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
    use RendersAttachmentPreview;

    protected static string $resource = ForApprovalReplenishmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit_item_review')
                ->label('Approve')
                ->color('success')
                ->visible(fn($record) => $this->canCurrentUserReview($record))
                ->form(fn($record): array => $this->buildReviewFormSchema($record))
                ->modalHeading('Review Replenishment Items')
                ->modalSubmitActionLabel('Submit Review')
                ->action(function ($record, array $data): void {
                    try {
                        ForApprovalReplenishmentFacade::submitItemReview(
                            $record,
                            $data['approved_item_ids'] ?? [],
                            $data['remarks'] ?? null,
                            $data['step_form_data'] ?? [],
                            (bool)($data['reject_request'] ?? false),
                        );

                        Notification::make()
                            ->title('Replenishment review submitted.')
                            ->success()
                            ->send();

                        $this->redirect(ForApprovalReplenishmentResource::getUrl('index'));
                    } catch (RuntimeException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('replenish_amount')
                ->label('Replenish')
                ->color('primary')
                ->visible(fn($record) => $this->canApplyReplenishmentAmount($record))
                ->form([
                    TextInput::make('initial_amount')
                        ->label('Initial Amount')
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
                        ->label('Amount to Add')
                        ->numeric()
                        ->prefix('PHP')
                        ->minValue(0.01)
                        ->required(),
                ])
                ->modalHeading('Apply Replenishment Amount')
                ->modalSubmitActionLabel('Apply')
                ->action(function ($record, array $data): void {
                    try {
                        ForApprovalReplenishmentFacade::applyReplenishmentAmount(
                            $record,
                            (float)($data['amount_to_add'] ?? 0),
                        );

                        Notification::make()
                            ->title('Replenishment amount applied.')
                            ->success()
                            ->send();

                        $this->redirect(ForApprovalReplenishmentResource::getUrl('view', ['record' => $record]));
                    } catch (RuntimeException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Replenishment Details')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Replenishment #'),
                        TextEntry::make('revolvingFund.fund_code')
                            ->label('Fund Code'),
                        TextEntry::make('revolvingFund.user.name')
                            ->label('Requestor'),
                        TextEntry::make('initial_amount')
                            ->label('Initial Amount')
                            ->money('PHP'),
                        TextEntry::make('total_amount')
                            ->label('Approved Total')
                            ->money('PHP'),
                        TextEntry::make('amount_to_return')
                            ->label('Amount to Return/Deduct')
                            ->money('PHP'),
                        TextEntry::make('remaining_amount')
                            ->label('Remaining Amount')
                            ->money('PHP'),
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
                            ->placeholder('-'),
                        TextEntry::make('replenished_at')
                            ->label('Replenished At')
                            ->dateTime('F d, Y h:i A')
                            ->placeholder('-'),
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

        if (!in_array((string)$record->status, ['approved', 'rejected'], true)) {
            return false;
        }

        if (filled($record->replenished_at)) {
            return false;
        }

        $latestAction = $record->replenishmentApprovals()
            ->whereIn('status', ['approved', 'declined'])
            ->orderByDesc('acted_at')
            ->orderByDesc('id')
            ->first();

        if (!$latestAction) {
            return false;
        }

        return (int)$latestAction->approved_by === (int)$user->id;
    }
}
