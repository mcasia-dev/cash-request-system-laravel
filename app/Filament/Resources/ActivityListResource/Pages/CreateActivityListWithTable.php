<?php

namespace App\Filament\Resources\ActivityListResource\Pages;

use App\Enums\CashRequest\ModeOfTransfer;
use App\Enums\CashRequest\NatureOfRequestEnum;
use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Filament\Resources\ActivityListResource;
use App\Models\CashRequest\ActivityList;
use App\Models\CashRequest\ApprovalRule;
use App\Models\CashRequest\CashRequest;
use App\Models\User;
use App\Services\CashRequest\CashRequestApprovalFlowService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateActivityListWithTable extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = ActivityListResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.create-activity-list';

    public array $data = [];
    public ?int $draftCashRequestId = null;
    public ?string $draftNatureOfRequest = null;
    public ?string $draftModeOfTransfer = null;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function mount(): void
    {
        $this->loadDraftCashRequestState();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->columns(2)
            ->schema([
                Section::make('Nature of Request')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('nature_of_request')
                            ->options(NatureOfRequestEnum::filamentOptions())
                            ->live()
                            ->visible(fn() => blank($this->draftCashRequestId))
                            ->required(fn() => blank($this->draftCashRequestId))
                            ->dehydrated(fn() => blank($this->draftCashRequestId)),

                        Select::make('mode_of_transfer')
                            ->label('Mode of Transfer')
                            ->options(ModeOfTransfer::filamentOptions())
                            ->visible(function (Get $get): bool {
                                return blank($this->draftCashRequestId) && $this->isCashAdvanceNature($get('nature_of_request'));
                            })
                            ->required(function (Get $get): bool {
                                return blank($this->draftCashRequestId) && $this->isCashAdvanceNature($get('nature_of_request'));
                            })
                            ->dehydrated(function (Get $get): bool {
                                return blank($this->draftCashRequestId) && $this->isCashAdvanceNature($get('nature_of_request'));
                            }),

                        Placeholder::make('selected_nature_of_request')
                            ->label('Selected Nature of Request')
                            ->visible(fn() => filled($this->draftCashRequestId))
                            ->content(fn() => (string)$this->draftNatureOfRequest),

                        Placeholder::make('selected_mode_of_transfer')
                            ->label('Selected Mode of Transfer')
                            ->visible(fn() => filled($this->draftCashRequestId) && $this->isCashAdvanceNature())
                            ->content(fn() => $this->resolveModeOfTransferLabel($this->draftModeOfTransfer)),
                    ]),

                Section::make('Activity Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('activity_name')
                            ->label('Activity Name')
                            ->required(),

                        DatePicker::make('activity_date')
                            ->label('Activity Date')
                            ->minDate(now()->toDateString())
                            ->prefixIcon('heroicon-m-calendar')
                            ->native(false)
                            ->required(),

                        TextInput::make('activity_venue')
                            ->label('Activity Venue')
                            ->required(),

                        TextInput::make('requesting_amount')
                            ->label('Requesting Amount')
                            ->prefix('PHP ')
                            ->required()
                            ->numeric()
                            ->maxValue(function (Get $get): ?float {
                                $natureOfRequest = $this->draftNatureOfRequest ?? $get('nature_of_request');

                                return $this->getConfiguredMaxAmountForNature($natureOfRequest);
                            }),

                        SpatieMediaLibraryFileUpload::make('attachment')
                            ->collection('attachments')
                            ->multiple()
                            ->responsiveImages()
                            ->nullable(),

                        Textarea::make('purpose')
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ]);
    }

    public function create(): void
    {
        $formData = $this->form->getState();
        $natureOfRequest = $formData['nature_of_request'] ?? $this->draftNatureOfRequest;

        if ($this->hasActiveRequestForNature($natureOfRequest, $this->draftCashRequestId)) {
            Notification::make()
                ->title('Existing request found')
                ->body('You already have a pending request with the same nature that is not yet liquidated.')
                ->warning()
                ->send();

            return;
        }

        $cashRequest = $this->getOrCreateDraftCashRequest($formData['nature_of_request'] ?? null, $formData['mode_of_transfer'] ?? null);

        $activityList = ActivityList::create([
            'user_id' => Auth::id(),
            'cash_request_id' => $cashRequest->id,
            'control_no' => Auth::user()->control_no,
            'activity_name' => $formData['activity_name'],
            'activity_date' => $formData['activity_date'],
            'activity_venue' => $formData['activity_venue'],
            'requesting_amount' => $formData['requesting_amount'],
            'purpose' => $formData['purpose'],
            'status' => 'pending',
        ]);

        $this->form->model($activityList)->saveRelationships();
        $this->loadDraftCashRequestState();
        $this->form->fill();
        $this->dispatch('$refresh');

        Notification::make()
            ->title('Activity added')
            ->body('The activity has been added to the list below.')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActivityList::query()
                    ->where('user_id', Auth::id())
                    ->when(
                        filled($this->draftCashRequestId),
                        fn($query) => $query->where('cash_request_id', $this->draftCashRequestId),
                        fn($query) => $query->whereNull('id')
                    )
            )
            ->columns([
                TextColumn::make('activity_name')
                    ->label('Activity Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('activity_venue')
                    ->label('Activity Venue')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('activity_date')
                    ->label('Activity Date')
                    ->sortable()
                    ->date(),

                TextColumn::make('cashRequest.nature_of_request')
                    ->label('Nature of Request')
                    ->badge(),

                TextColumn::make('requesting_amount')
                    ->label('Requesting Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('purpose')
                    ->words(4),
            ])
            ->headerActions([
                Action::make('submitCashRequest')
                    ->label('Submit Cash Request')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Checkbox::make('is_approved_the_authority_to_deduct')
                            ->label(config('fund_deduction_authorization.authorization_statement'))
                            ->accepted()
                            ->required(),
                    ])
                    ->modalHeading('Submit Cash Request')
                    ->modalDescription('Please review and accept the required declarations before submitting this cash request.')
                    ->modalSubmitActionLabel('Yes, submit')
                    ->modalCancelActionLabel('Cancel')
                    ->action(fn(array $data) => $this->submitCashRequest($data)),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->form([
                            TextInput::make('activity_name')
                                ->label('Activity Name')
                                ->required(),

                            DatePicker::make('activity_date')
                                ->label('Activity Date')
                                ->minDate(now()->toDateString())
                                ->required(),

                            TextInput::make('activity_venue')
                                ->label('Activity Venue')
                                ->required(),

                            TextInput::make('requesting_amount')
                                ->label('Requesting Amount')
                                ->prefix('PHP ')
                                ->required()
                                ->numeric()
                                ->maxValue(fn(): ?float => $this->getConfiguredMaxAmountForNature($this->draftNatureOfRequest)),

                            SpatieMediaLibraryFileUpload::make('attachment')
                                ->collection('attachments')
                                ->multiple()
                                ->responsiveImages(),

                            Textarea::make('purpose')
                                ->columnSpanFull()
                                ->required(),
                        ]),
                    DeleteAction::make(),
                ]),
            ]);
    }

    private function submitCashRequest(array $data = []): void
    {
        $failureMessage = 'Nothing to submit';
        $submittedCashRequest = null;
        $submittedBy = null;

        $submitted = DB::transaction(function () use (&$failureMessage, &$submittedCashRequest, &$submittedBy, $data): bool {
            $user = Auth::user();
            $cashRequest = $this->getDraftCashRequest();

            if (!$cashRequest) {
                $failureMessage = 'Nothing to submit';

                return false;
            }

            $activities = ActivityList::query()
                ->where('user_id', Auth::id())
                ->where('cash_request_id', $cashRequest->id)
                ->get();

            if ($activities->isEmpty()) {
                $failureMessage = 'Nothing to submit';

                return false;
            }

            $totalRequestingAmount = (float)$activities->sum('requesting_amount');
            $maxAllowedAmount = $this->getConfiguredMaxAmountForNature($cashRequest->nature_of_request);

            if ($maxAllowedAmount !== null && $totalRequestingAmount > $maxAllowedAmount) {
                $failureMessage = 'Total requesting amount must not be greater than PHP ' . number_format($maxAllowedAmount, 2) . '.';

                return false;
            }

            if ($this->hasActiveRequestForNature($cashRequest->nature_of_request, $cashRequest->id)) {
                $failureMessage = 'You already have a pending request with the same nature that is not yet liquidated.';

                return false;
            }

            $cashRequest->update([
                'requesting_amount' => $totalRequestingAmount,
                'status' => Status::PENDING->value,
                'status_remarks' => StatusRemarks::REQUEST_SUBMITTED->value,
                'is_approved_the_authority_to_deduct' => (bool)($data['is_approved_the_authority_to_deduct'] ?? false),
            ]);

            try {
                app(CashRequestApprovalFlowService::class)->initializeApprovals($cashRequest);
            } catch (\RuntimeException $exception) {
                $failureMessage = $exception->getMessage();

                return false;
            }

            activity()
                ->causedBy($user)
                ->performedOn($cashRequest)
                ->event('created')
                ->withProperties([
                    'request_no' => $cashRequest->request_no,
                    'activity_name' => $cashRequest->activity_name,
                    'requesting_amount' => $cashRequest->requesting_amount,
                    'status' => Status::PENDING->value,
                    'status_remarks' => StatusRemarks::REQUEST_SUBMITTED->value,
                ])
                ->log("Cash request {$cashRequest->request_no} was submitted by {$user->name} ({$user->position})");

            $submittedCashRequest = $cashRequest->fresh();
            $submittedBy = $user;

            return true;
        });

        if (!$submitted) {
            Notification::make()
                ->title($failureMessage)
                ->warning()
                ->send();

            return;
        }

        if ($submittedCashRequest && $submittedBy) {
            $this->notifyApprovers($submittedCashRequest, $submittedBy);
        }

        $this->loadDraftCashRequestState();
        $this->form->fill();
        $this->dispatch('$refresh');

        Notification::make()
            ->title('Cash request submitted')
            ->success()
            ->send();

        redirect()->route('filament.admin.resources.cash-requests.index');
    }

    public function getTitle(): string
    {
        return 'Create Cash Request';
    }

    private function getDraftCashRequest(): ?CashRequest
    {
        return CashRequest::query()
            ->where('user_id', Auth::id())
            ->whereNull('status_remarks')
            ->latest('id')
            ->first();
    }

    private function getOrCreateDraftCashRequest(?string $natureOfRequest, ?string $modeOfTransfer): CashRequest
    {
        $cashRequest = $this->getDraftCashRequest();

        if ($cashRequest) {
            return $cashRequest;
        }

        $attributes = [
            'user_id' => Auth::id(),
            'nature_of_request' => $natureOfRequest,
            'requesting_amount' => 0,
            'status' => Status::PENDING->value,
        ];

        if (filled($modeOfTransfer)) {
            $attributes['mode_of_transfer'] = $modeOfTransfer;
        }

        return CashRequest::create($attributes);
    }

    private function loadDraftCashRequestState(): void
    {
        $cashRequest = $this->getDraftCashRequest();

        $this->draftCashRequestId = $cashRequest?->id;
        $this->draftNatureOfRequest = $cashRequest?->nature_of_request;
        $this->draftModeOfTransfer = $cashRequest?->mode_of_transfer;
    }

    private function getConfiguredMaxAmountForNature(?string $nature)
    {
        if (blank($nature) || $nature !== NatureOfRequestEnum::PETTY_CASH->value) {
            return null;
        }

        return ApprovalRule::query()
            ->where('is_active', true)
            ->where('nature', $nature)
            ->where(function ($query) {
                $query->whereNull('min_amount')
                    ->orWhere('min_amount', '<=', 0);
            })
            ->orderByRaw('CASE WHEN max_amount IS NULL THEN 1 ELSE 0 END')
            ->orderBy('max_amount')
            ->value('max_amount');
    }

    private function hasActiveRequestForNature(?string $nature, ?int $excludeRequestId = null): bool
    {
        if (blank($nature)) {
            return false;
        }

        return CashRequest::query()
            ->where('user_id', Auth::id())
            ->where('nature_of_request', $nature)
            ->when(
                filled($excludeRequestId),
                fn($query) => $query->where('id', '!=', $excludeRequestId)
            )
            ->whereNotIn('status', [Status::LIQUIDATED->value, Status::CANCELLED->value, Status::REJECTED->value])
            ->exists();
    }

    private function isCashAdvanceNature(?string $natureFromForm = null): bool
    {
        $nature = $natureFromForm ?? $this->draftNatureOfRequest;

        if (blank($nature)) {
            return false;
        }

        $normalizedNature = strtolower(trim((string)$nature));

        return $normalizedNature === NatureOfRequestEnum::CASH_ADVANCE->value
            || $normalizedNature === 'cash_advance';
    }

    private function resolveModeOfTransferLabel(?string $modeOfTransfer): string
    {
        if (blank($modeOfTransfer)) {
            return '-';
        }

        $normalizedMode = strtolower(trim(str_replace('_', ' ', $modeOfTransfer)));
        $options = ModeOfTransfer::filamentOptions();

        return $options[$normalizedMode] ?? ucwords($normalizedMode);
    }

    private function notifyApprovers(CashRequest $cashRequest, User $requestor): void
    {
        $departmentHeads = User::query()
            ->role('department_head')
            ->where('department_id', $requestor->department_id)
            ->get();

        $superAdmins = User::query()
            ->role('super_admin')
            ->get();

        $recipients = $departmentHeads
            ->merge($superAdmins)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('New Cash Request Submitted')
            ->body("{$requestor->name} submitted {$cashRequest->request_no} for approval.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.for-approval-requests.view', ['record' => $cashRequest->id])),
            ])
            ->sendToDatabase($recipients);
    }
}
