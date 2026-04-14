<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RevolvingFundModeOfTransferResource\Pages;
use App\Filament\Resources\RevolvingFundModeOfTransferResource\RelationManagers;
use App\Models\RevolvingFundModeOfTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RevolvingFundModeOfTransferResource extends Resource
{
    protected static ?string $model = RevolvingFundModeOfTransfer::class;
    protected static ?string $navigationGroup = 'Revolving Funds';
    protected static ?string $navigationLabel = 'Mode of Transfer';
    protected static ?string $label = 'Mode of Transfer';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Toggle::make('is_published')
                    ->label('Is Published')
                    ->default(true)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Is Published')
                    ->sortable()
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
            'index' => Pages\ListRevolvingFundModeOfTransfers::route('/'),
            'create' => Pages\CreateRevolvingFundModeOfTransfer::route('/create'),
            'edit' => Pages\EditRevolvingFundModeOfTransfer::route('/{record}/edit'),
        ];
    }
}
