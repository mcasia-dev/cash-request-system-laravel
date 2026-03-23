<?php

namespace App\Filament\Resources;

use App\Enums\RevolvingFund\FieldWorkDays;
use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use App\Filament\Resources\RevolvingFundResource\Pages;
use App\Models\RevolvingFund\RevolvingFund;
use App\Models\RevolvingFundPurpose;
use Facades\App\Services\RevolvingFund\RevolvingFundService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RevolvingFundResource extends Resource
{
    protected static ?string $model = RevolvingFund::class;
    protected static ?string $navigationGroup = 'Revolving Funds';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (!$user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $base = parent::getEloquentQuery()->where('added_by', $user->id);

        if (!$user->hasRole('department_head') || !$user->department_id) {
            return $base;
        }

        return parent::getEloquentQuery()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('added_by', $user->id)
                    ->orWhere(function (Builder $departmentHeadScope) use ($user): void {
                        $departmentHeadScope
                            ->whereHas('user', fn(Builder $q) => $q->where('department_id', $user->department_id))
                            ->whereHas('replenishments', fn(Builder $q) => $q->whereIn('status', ['pending', 'returned']));
                    });
            });
    }

    public static function form(Form $form): Form
    {
        $otherPurposeId = RevolvingFundPurpose::query()
            ->where('purpose', 'Others')
            ->value('id');

        return $form
            ->schema([
                TextInput::make('initial_amount')
                    ->label('Initial Amount')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('PHP')
                    ->live()
                    ->afterStateUpdated(fn(Set $set, $state) => $set('remaining_amount', $state)),

                TextInput::make('remaining_amount')
                    ->label('Remaining Amount')
                    ->minValue(0)
                    ->readOnly()
                    ->prefix('PHP'),

                Select::make('user_id')
                    ->label('Employee')
                    ->relationship(
                        'user',
                        'name',
                        modifyQueryUsing: function (Builder $query): void {
                            $authUser = Auth::user();

                            if (!$authUser) {
                                $query->whereRaw('1 = 0');
                                return;
                            }

                            if ($authUser->isSuperAdmin()) {
                                return;
                            }

                            $query->where('department_id', $authUser->department_id);
                        }
                    )
                    ->required(fn() => Auth::user()?->hasRole('department_head') || Auth::user()?->isSuperAdmin())
                    ->visible(fn() => Auth::user()?->hasRole('department_head') || Auth::user()?->isSuperAdmin())
                    ->preload()
                    ->searchable()
                    ->helperText('Employee who will receive the fund.')
                    ->rule(function (?Model $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($record): void {
                            $existingFund = RevolvingFund::query()
                                ->where('user_id', $value)
                                ->when($record, fn($query) => $query->whereKeyNot($record->getKey()))
                                ->where(function ($query) {
                                    $query->whereNull('status')
                                        ->orWhereIn('status', [
                                            Status::PENDING->value,
                                            Status::IN_PROGRESS->value,
                                            Status::APPROVED->value,
                                            Status::RELEASED->value,
                                        ]);
                                })
                                ->exists();

                            if ($existingFund) {
                                $fail('This employee has already an active revolving fund request.');
                            }
                        };
                    }),

                Hidden::make('user_id')
                    ->default(fn() => Auth::id())
                    ->visible(fn() => !(Auth::user()?->hasRole('department_head') || Auth::user()?->isSuperAdmin()))
                    ->dehydrated(true),

                TextInput::make('area_of_assignment')
                    ->label('Area of Assignment')
                    ->required(),

                Select::make('revolving_fund_mode_of_transfer_id')
                    ->label('Mode of Transfer')
                    ->relationship('modeOfTransfer', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),

                Grid::make(1)
                    ->schema([
                        Select::make('purposes')
                            ->label('Revolving Fund Purpose')
                            ->relationship(
                                'purposes',
                                'purpose',
                                modifyQueryUsing: fn(Builder $query) => $query->where('is_published', true),
                            )
                            ->multiple()
                            ->required()
                            ->live()
                            ->preload()
                            ->searchable()
                            ->afterStateUpdated(function (Set $set, $state) use ($otherPurposeId): void {
                                if (!$otherPurposeId) {
                                    return;
                                }

                                $selected = collect($state ?? [])->map(fn($value) => (int)$value)->all();

                                if (!in_array((int)$otherPurposeId, $selected, true)) {
                                    $set('other_purpose', null);
                                }
                            }),

                        TextInput::make('other_purpose')
                            ->label('Specify Other Purpose')
                            ->maxLength(255)
                            ->required(fn(Get $get) => $otherPurposeId && in_array((int)$otherPurposeId, collect($get('purposes') ?? [])->map(fn($value) => (int)$value)->all(), true))
                            ->visible(fn(Get $get) => $otherPurposeId && in_array((int)$otherPurposeId, collect($get('purposes') ?? [])->map(fn($value) => (int)$value)->all(), true)),
                    ]),

                Section::make('Field Work Assignment')
                    ->description('Pick days via checkboxes and set the time range for each selected day.')
                    ->schema([
                        CheckboxList::make('field_work_days')
                            ->label('Days')
                            ->options(FieldWorkDays::filamentOptions())
                            ->columns(6)
                            ->bulkToggleable()
                            ->live()
                            ->afterStateHydrated(function (Set $set, Get $get, $state): void {
                                if ($state !== null) {
                                    return;
                                }

                                $existing = collect($get('field_work_assignment') ?? [])->pluck('day')->filter()->values()->all();
                                $set('field_work_days', $existing);
                            })
                            ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                                $selectedDays = collect($state ?? [])->values();
                                $existingAssignments = collect($get('field_work_assignment') ?? []);

                                $newAssignments = $selectedDays->map(function (string $day) use ($existingAssignments) {
                                    $existing = $existingAssignments->firstWhere('day', $day);

                                    return [
                                        'day' => $day,
                                        'time_from' => $existing['time_from'] ?? null,
                                        'time_to' => $existing['time_to'] ?? null,
                                    ];
                                })->all();

                                $set('field_work_assignment', $newAssignments);
                            }),

                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                Placeholder::make('field_work_assignment_count')
                                    ->label('Selected days')
                                    ->content(fn(Get $get) => count($get('field_work_days') ?? [])),
                                Placeholder::make('field_work_assignment_hint')
                                    ->label('Tip')
                                    ->content('Times are saved per checked day.'),
                            ])
                            ->columnSpanFull(),

                        Repeater::make('field_work_assignment')
                            ->label('Schedule')
                            ->schema([
                                TextInput::make('day')
                                    ->label('Day')
                                    ->formatStateUsing(fn($state) => $state ? ucfirst($state) : '')
                                    ->disabled()
                                    ->dehydrated(true),

                                TimePicker::make('time_from')
                                    ->label('Time From')
                                    ->seconds(false)
                                    ->format('h:i A')
                                    ->required(),

                                TimePicker::make('time_to')
                                    ->label('Time To')
                                    ->seconds(false)
                                    ->format('h:i A')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->visible(fn(Get $get) => count($get('field_work_assignment') ?? []) > 0)
                            ->required(fn(Get $get) => count($get('field_work_days') ?? []) > 0)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),

                Hidden::make('status_remarks')
                    ->default(StatusRemarks::FUND_REQUEST_SUBMITTED->value)
                    ->required(),

                Hidden::make('added_by')
                    ->default(fn() => Auth::id())
                    ->required(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fund_code')
                    ->label('Fund Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('initial_amount')
                    ->label('Initial Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label('Remaining Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purposes.purpose')
                    ->label('Purposes')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('other_purpose')
                    ->label('Other Purpose')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        Status::PENDING->value => 'warning',
                        Status::IN_PROGRESS->value => 'secondary',
                        Status::APPROVED->value => 'success',
                        Status::REJECTED->value => 'danger',
                        Status::REPLENISHED->value => 'info',
                        Status::RELEASED->value => 'info',
                        default => 'secondary',
                    }),

                TextColumn::make('status_remarks')
                    ->label('Status Remarks')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\Action::make('tracking')
                        ->label('Tracking Status')
                        ->color('warning')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->url(fn($record) => route('filament.admin.resources.revolving-funds.tracking', ['record' => $record])),


                    Tables\Actions\EditAction::make()
                        ->visible(fn($record) => RevolvingFundService::isVisibleIfPending($record)),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRevolvingFunds::route('/'),
            'create' => Pages\CreateRevolvingFund::route('/create'),
            'edit' => Pages\EditRevolvingFund::route('/{record}/edit'),
            'view' => Pages\ViewRevolvingFund::route('/{record}/view'),
            'tracking' => Pages\TrackRevolvingFund::route('/{record}/tracking'),
        ];
    }
}
