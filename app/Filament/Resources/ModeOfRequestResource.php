<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModeOfRequestResource\Pages;
use App\Models\Department;
use App\Models\Reimbursement\ModeOfRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class ModeOfRequestResource extends Resource
{
    protected static ?string $model = ModeOfRequest::class;
    protected static ?string $navigationGroup = 'Administrator';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('code')
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_active')
                    ->required(),

                Forms\Components\Repeater::make('reimbursementModeApprovals')
                    ->label('Approval Flow (Per Mode)')
                    ->relationship()
                    ->defaultItems(1)
                    ->addActionLabel('Add Approval Step')
                    ->schema([
                        Forms\Components\TextInput::make('step_no')
                            ->label('Step No.')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->options(Department::query()->orderBy('department_name')->pluck('department_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('role_name')
                            ->label('Role Name')
                            ->options(Role::query()->orderBy('name')->pluck('name', 'name'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('assigned_user_ids')
                            ->label('Specific Users (Optional)')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get): array {
                                $role = $get('role_name');

                                if (! filled($role)) {
                                    return [];
                                }

                                return User::query()
                                    ->whereHas('roles', fn($query) => $query->where('name', $role))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->helperText('If empty, all users from selected role can approve. If set, only selected users can approve this step.')
                            ->visible(fn(Get $get) => filled($get('role_name')))
                            ->disabled(fn() => ! (bool) Auth::user()?->isSuperAdmin()),

                        Forms\Components\Toggle::make('required')
                            ->label('Required')
                            ->default(true),

                        Forms\Components\Toggle::make('can_approve')
                            ->label('Can Approve')
                            ->default(true),

                        Forms\Components\Toggle::make('can_reject')
                            ->label('Can Reject')
                            ->default(true),

                        Forms\Components\Repeater::make('form_schema')
                            ->label('Optional Custom Form')
                            ->defaultItems(0)
                            ->addActionLabel('Add Field')
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->label('Field Key')
                                    ->required()
                                    ->alphaDash()
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('label')
                                    ->label('Field Label')
                                    ->required()
                                    ->maxLength(100),

                                Forms\Components\Select::make('type')
                                    ->label('Field Type')
                                    ->required()
                                    ->options([
                                        'text' => 'Text',
                                        'number' => 'Number',
                                        'textarea' => 'Textarea',
                                        'date' => 'Date',
                                    ])
                                    ->default('text'),

                                Forms\Components\Toggle::make('required')
                                    ->label('Required')
                                    ->default(false),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reimbursementModeApprovals_count')
                    ->label('Approval Steps')
                    ->counts('reimbursementModeApprovals')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModeOfRequests::route('/'),
            'create' => Pages\CreateModeOfRequest::route('/create'),
            'edit' => Pages\EditModeOfRequest::route('/{record}/edit'),
        ];
    }
}
