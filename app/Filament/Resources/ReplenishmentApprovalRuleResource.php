<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReplenishmentApprovalRuleResource\Pages;
use App\Models\RevolvingFund\ReplenishmentApprovalRule;
use App\Models\Role;
use App\Models\User;
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

class ReplenishmentApprovalRuleResource extends Resource
{
    protected static ?string $model = ReplenishmentApprovalRule::class;
    protected static ?string $navigationGroup = 'Replenishments';
    protected static ?string $navigationLabel = 'Replenishment Approval Rules';
    protected static ?string $label = 'Replenishment Approval Rule';
    protected static ?string $pluralLabel = 'Replenishment Approval Rules';
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

                        Toggle::make('can_verify')
                            ->label('Can Verify')
                            ->helperText('Enable this for the treasury step that can verify and apply the replenished amount.')
                            ->default(false),

                        Toggle::make('can_replenish')
                            ->label('Can Replenish')
                            ->helperText('Enable this for the step allowed to apply the replenishment amount after approval.')
                            ->default(false),

                        Toggle::make('use_item_selection')
                            ->label('Use Item Selection')
                            ->helperText('If disabled, this approver approves/rejects the request without selecting items.')
                            ->default(true),

                        Repeater::make('form_schema')
                            ->label('Optional Custom Form')
                            ->helperText('Optional. Configure extra fields for this approver step (e.g. Voucher Number for Treasury Staff).')
                            ->defaultItems(0)
                            ->addActionLabel('Add Field')
                            ->schema([
                                TextInput::make('key')
                                    ->label('Field Key')
                                    ->required()
                                    ->alphaDash()
                                    ->maxLength(50)
                                    ->helperText('Database key for the value, e.g. voucher_no'),

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
            'index' => Pages\ListReplenishmentApprovalRules::route('/'),
            'create' => Pages\CreateReplenishmentApprovalRule::route('/create'),
            'edit' => Pages\EditReplenishmentApprovalRule::route('/{record}/edit'),
        ];
    }
}
