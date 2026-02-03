<?php
namespace App\Filament\Resources;

use App\Enums\User\AccountStatus;
use App\Enums\User\Status;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model           = User::class;
    protected static ?string $navigationGroup = 'Administrator';
    protected static ?string $slug            = 'admin-users';
    protected static ?string $navigationLabel = 'User';
    protected static ?string $navigationIcon  = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('middle_name')
                    ->label('Middle Name')
                    ->nullable()
                    ->maxLength(255),

                TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('position')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('password')
                    ->revealable()
                    ->password()
                    ->required()
                    ->maxLength(255),

                TextInput::make('contact_number')
                    ->label('Contact Number')
                    ->required()
                    ->numeric()
                    ->maxLength(255),

                Select::make('department_id')
                    ->relationship('department', 'department_name')
                    ->preload()
                    ->searchable()
                    ->required(),

                Select::make('account_status')
                    ->label('Account Status')
                    ->required()
                    ->options(AccountStatus::filamentOptions()),

                Select::make('status')
                    ->options(Status::filamentOptions())
                    ->required(),

                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('control_no')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('position')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('email')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('contact_number')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('department.department_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('signature_number')
                    ->searchable(),

                TextColumn::make('account_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Status::PENDING->value     => 'warning',
                        Status::APPROVED->value    => 'success',
                        Status::DISAPPROVED->value => 'danger',
                        default                    => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        AccountStatus::SUSPENDED->value => 'warning',
                        AccountStatus::ACTIVE->value    => 'success',
                        AccountStatus::BLOCKED->value   => 'danger',
                        default                         => 'secondary',
                    })
                    ->sortable(),

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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
