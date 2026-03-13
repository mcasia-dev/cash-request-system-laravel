<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RevolvingFundResource\Pages;
use App\Models\RevolvingFund;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RevolvingFundResource extends Resource
{
    protected static ?string $model = RevolvingFund::class;
    protected static ?string $navigationGroup = 'Cash Requests';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Revolving Funds';
    protected static ?string $label = 'Revolving Fund';
    protected static ?string $pluralLabel = 'Revolving Funds';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('amount')
                    ->label('Amount of Replenish')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('PHP'),

                TextInput::make('position')
                    ->label('Position (Who Receive)')
                    ->required()
                    ->maxLength(255),

                Hidden::make('user_id')
                    ->default(fn() => Auth::id())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Added By')
                    ->searchable()
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
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListRevolvingFunds::route('/'),
            'create' => Pages\CreateRevolvingFund::route('/create'),
            'edit' => Pages\EditRevolvingFund::route('/{record}/edit'),
        ];
    }
}

