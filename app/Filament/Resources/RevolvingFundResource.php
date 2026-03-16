<?php

namespace App\Filament\Resources;

use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use App\Filament\Resources\RevolvingFundResource\Pages;
use App\Models\RevolvingFund\RevolvingFund;
use Facades\App\Services\RevolvingFund\RevolvingFundService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
                    ->label('User')
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
                    ->required()
                    ->preload()
                    ->searchable()
                    ->helperText('User who will receive the fund.')
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
                                        ]);
                                })
                                ->exists();

                            if ($existingFund) {
                                $fail('This user already has an active revolving fund request.');
                            }
                        };
                    }),

                Hidden::make('status_remarks')
                    ->default(StatusRemarks::FUND_REQUEST_SUBMITTED->value)
                    ->required(),

                Hidden::make('added_by')
                    ->default(fn() => Auth::id())
                    ->required(),
            ]);
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

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        Status::PENDING->value => 'warning',
                        Status::IN_PROGRESS->value => 'warning',
                        Status::APPROVED->value => 'success',
                        Status::REJECTED->value => 'danger',
                        Status::REPLENISHED->value => 'info',
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
        ];
    }
}
