<?php
namespace App\Filament\Resources\ActivityListResource\Pages;

use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CashRequest;
use App\Models\ActivityList;
use App\Enums\CashRequest\Status;
use App\Enums\NatureOfRequestEnum;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
// use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Resources\ActivityListResource;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class CreateActivityListWithTable extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource        = ActivityListResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view            = 'filament.pages.create-activity-list';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->columns(2)
            ->schema([
                Select::make('nature_of_request')
                    ->options(NatureOfRequestEnum::filamentOptions())
                    ->live()
                    ->required(),

                TextInput::make('activity_name')
                    ->label('Activity Name')
                    ->required(),

                DatePicker::make('activity_date')
                    ->label('Activity Date')
                    ->minDate(now())
                    ->required(),

                TextInput::make('activity_venue')
                    ->label('Activity Venue')
                    ->required(),

                TextInput::make('requesting_amount')
                    ->label('Requesting Amount')
                    ->prefix('₱')
                    ->required()
                    ->numeric()
                    ->maxValue(fn($get) => $get('nature_of_request') === NatureOfRequestEnum::PETTY_CASH->value ? 1500 : null),

                SpatieMediaLibraryFileUpload::make('attachment')
                    ->collection('attachments'),

                Textarea::make('purpose')
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public function create(): void
    {
        ActivityList::create([
            'user_id'           => Auth::id(),
            'control_no'        => Auth::user()->control_no,
            'nature_of_request' => $this->data['nature_of_request'],
            'activity_name'     => $this->data['activity_name'],
            'activity_date'     => $this->data['activity_date'],
            'activity_venue'    => $this->data['activity_venue'],
            'requesting_amount' => $this->data['requesting_amount'],
            'purpose'           => $this->data['purpose'],
        ]);

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
            ->query(ActivityList::where('user_id', Auth::id()))
            ->columns([
                TextColumn::make('nature_of_request')
                    ->label('Nature of Request')
                    ->badge()
                    ->sortable()
                    ->searchable(),

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

                TextColumn::make('requesting_amount')
                    ->label('Requesting Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('purpose'),
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
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    private function submitCashRequest(): void
    {
        DB::transaction(function () {

            $activities = ActivityList::where('user_id', Auth::id())->get();

            if ($activities->isEmpty()) {
                return;
            }

            foreach ($activities as $activity) {
                $user        = Auth::user();
                $cashRequest = CashRequest::create([
                    'user_id'           => $activity->user_id,
                    'activity_name'     => $activity->activity_name,
                    'activity_date'     => $activity->activity_date,
                    'activity_venue'    => $activity->activity_venue,
                    'purpose'           => $activity->purpose,
                    'nature_of_request' => $activity->nature_of_request,
                    'requesting_amount' => $activity->requesting_amount,
                ]);

                // Log each cash requests created
                activity()
                    ->causedBy($user)
                    ->performedOn($cashRequest)
                    ->event('created')
                    ->withProperties([
                        'request_no'        => $cashRequest->request_no,
                        'activity_name'     => $cashRequest->activity_name,
                        'requesting_amount' => $cashRequest->requesting_amount,
                        'status'            => Status::PENDING->value,
                    ])
                    ->log("Cash request {$cashRequest->request_no} was submitted by {$user->name} ({$user->position})");

                $activity->delete();
            }
        });
    }

    public function getTitle(): string
    {
        return 'Create Cash Request';
    }

}
