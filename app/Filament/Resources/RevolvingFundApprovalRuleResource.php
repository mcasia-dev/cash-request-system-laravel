<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RevolvingFundApprovalRuleResource\Pages;
use App\Models\RevolvingFund\RevolvingFundApprovalRule;
use App\Models\Department;
use App\Models\User;
use App\Models\Role;
use Filament\Forms\Get;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RevolvingFundApprovalRuleResource extends Resource
{
    protected static ?string $model = RevolvingFundApprovalRule::class;
    protected static ?string $navigationGroup = 'Revolving Funds';
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('min_amount')
                    ->label('Min Amount')
                    ->numeric()
                    ->prefix('PHP')
                    ->minValue(0)
                    ->nullable(),

                TextInput::make('max_amount')
                    ->label('Max Amount')
                    ->numeric()
                    ->prefix('PHP')
                    ->minValue(0)
                    ->nullable()
                    ->helperText('Leave blank for no upper limit.'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),

                Repeater::make('steps')
                    ->label('Approver Roles')
                    ->relationship()
                    ->defaultItems(1)
                    ->addActionLabel('Add Role')
                    ->schema([
                        Select::make('role_name')
                            ->label('Approver Role')
                            ->options(fn() => Role::query()
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->toArray())
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->searchable(),

                        Select::make('department_id')
                            ->label('Department')
                            ->options(fn() => Department::query()->orderBy('department_name')->pluck('department_name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->helperText('Optional: limit this approver role to a specific department.'),

                        Select::make('assigned_user_ids')
                            ->label('Specific Users (Optional)')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn() => User::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->helperText('If empty, all users with this role can approve. If set, only the chosen users can approve this step.')
                            ->visible(fn(Get $get) => filled($get('role_name')) && (bool)Auth::user()?->isSuperAdmin()),

                        Toggle::make('can_approve')
                            ->label('Can Approve')
                            ->default(true),

                        Toggle::make('can_reject')
                            ->label('Can Reject')
                            ->default(true),

                        Repeater::make('form_schema')
                            ->label('Optional Custom Form')
                            ->defaultItems(0)
                            ->addActionLabel('Add Field')
                            ->schema([
                                TextInput::make('key')
                                    ->label('Field Key')
                                    ->required()
                                    ->alphaDash()
                                    ->maxLength(50),

                                TextInput::make('label')
                                    ->label('Field Label')
                                    ->required()
                                    ->maxLength(100),

                                Select::make('type')
                                    ->label('Field Type')
                                    ->required()
                                    ->options([
                                        'text' => 'Text',
                                        'number' => 'Number',
                                        'textarea' => 'Textarea',
                                        'date' => 'Date',
                                    ])
                                    ->default('text'),

                                Toggle::make('required')
                                    ->label('Required')
                                    ->default(false),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->grid(1)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('min_amount')
                    ->label('Min Amount')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state === null ? 'No minimum' : 'PHP ' . number_format((float)$state, 2)),

                TextColumn::make('max_amount')
                    ->label('Max Amount')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state === null ? 'No Limit' : 'PHP ' . number_format((float)$state, 2)),

                TextColumn::make('steps_count')
                    ->label('Approver Steps')
                    ->counts('steps'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('F d, Y H:i A')
                    ->timezone('Asia/Manila')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRevolvingFundApprovalRules::route('/'),
            'create' => Pages\CreateRevolvingFundApprovalRule::route('/create'),
            'edit' => Pages\EditRevolvingFundApprovalRule::route('/{record}/edit'),
        ];
    }
}
