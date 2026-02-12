<?php

namespace App\Filament\Resources\ActivityListResource\Pages;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Enums\NatureOfRequestEnum;
use App\Filament\Resources\ActivityListResource;
use App\Models\ActivityList;
use App\Models\ApprovalRule;
use App\Models\CashRequest;
use App\Services\CashRequestApprovalFlowService;
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

    protected static string $resource        = ActivityListResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view            = 'filament.pages.create-activity-list';

    public array $data = [];
    public ?int $draftCashRequestId = null;
    public ?string $draftNatureOfRequest = null;

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

                        Placeholder::make('selected_nature_of_request')
                            ->label('Selected Nature of Request')
                            ->visible(fn() => filled($this->draftCashRequestId))
                            ->content(fn() => (string) $this->draftNatureOfRequest),
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
                            ->collection('attachments'),

                        Textarea::make('purpose')
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ]);
    }

    public function create(): void
    {
        $formData = $this->form->getState();
        $cashRequest = $this->getOrCreateDraftCashRequest($formData['nature_of_request'] ?? null);

        $activityList = ActivityList::create([
            'user_id'           => Auth::id(),
            'cash_request_id'   => $cashRequest->id,
            'control_no'        => Auth::user()->control_no,
            'activity_name'     => $formData['activity_name'],
            'activity_date'     => $formData['activity_date'],
            'activity_venue'    => $formData['activity_venue'],
            'requesting_amount' => $formData['requesting_amount'],
            'purpose'           => $formData['purpose'],
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
                    ->modalHeading('Submit Cash Request')
                    ->modalDescription('Are you sure you want to submit this cash request? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, submit')
                    ->modalCancelActionLabel('Cancel')
                    ->action(fn() => $this->submitCashRequest()),
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
                                ->collection('attachments'),

                            Textarea::make('purpose')
                                ->columnSpanFull()
                                ->required(),
                        ]),
                    DeleteAction::make(),
                ]),
            ]);
    }

    private function submitCashRequest()
    {
        $failureMessage = 'Nothing to submit';

        $submitted = DB::transaction(function () use (&$failureMessage): bool {
            $user = Auth::user();
            $cashRequest = $this->getDraftCashRequest();

            if (! $cashRequest) {
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

            $totalRequestingAmount = (float) $activities->sum('requesting_amount');
            $maxAllowedAmount = $this->getConfiguredMaxAmountForNature($cashRequest->nature_of_request);

            if ($maxAllowedAmount !== null && $totalRequestingAmount > $maxAllowedAmount) {
                $failureMessage = 'Total requesting amount must not be greater than PHP ' . number_format($maxAllowedAmount, 2) . '.';

                return false;
            }

            $cashRequest->update([
                'requesting_amount' => $totalRequestingAmount,
                'status'            => Status::PENDING->value,
                'status_remarks'    => StatusRemarks::REQUEST_SUBMITTED->value,
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
                    'request_no'        => $cashRequest->request_no,
                    'activity_name'     => $cashRequest->activity_name,
                    'requesting_amount' => $cashRequest->requesting_amount,
                    'status'            => Status::PENDING->value,
                    'status_remarks'    => StatusRemarks::REQUEST_SUBMITTED->value,
                ])
                ->log("Cash request {$cashRequest->request_no} was submitted by {$user->name} ({$user->position})");

            return true;
        });

        if (! $submitted) {
            Notification::make()
                ->title($failureMessage)
                ->warning()
                ->send();

            return;
        }

        $this->loadDraftCashRequestState();
        $this->form->fill();
        $this->dispatch('$refresh');

        Notification::make()
            ->title('Cash request submitted')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.cash-requests.index');
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

    private function getOrCreateDraftCashRequest(?string $natureOfRequest): CashRequest
    {
        $cashRequest = $this->getDraftCashRequest();

        if ($cashRequest) {
            return $cashRequest;
        }

        return CashRequest::create([
            'user_id'           => Auth::id(),
            'nature_of_request' => $natureOfRequest,
            'requesting_amount' => 0,
            'status'            => Status::PENDING->value,
        ]);
    }

    private function loadDraftCashRequestState(): void
    {
        $cashRequest = $this->getDraftCashRequest();

        $this->draftCashRequestId = $cashRequest?->id;
        $this->draftNatureOfRequest = $cashRequest?->nature_of_request;
    }

    private function getConfiguredMaxAmountForNature(?string $nature): ?float
    {
        if (blank($nature)) {
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
}
