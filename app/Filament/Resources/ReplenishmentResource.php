<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReplenishmentResource\Pages;
use App\Models\RevolvingFund\Replenishment;
use App\Models\RevolvingFund\RevolvingFund;
use App\Enums\RevolvingFund\Status as RevolvingFundStatus;
use Facades\App\Services\Replenishment\ReplenishmentService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReplenishmentResource extends Resource
{
    protected static ?string $model = Replenishment::class;
    protected static ?string $navigationGroup = 'Replenishments';
    protected static ?string $navigationLabel = 'Replenishments';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    private static function eligibleRevolvingFundQuery(): Builder
    {
        return RevolvingFund::query()
            ->where('user_id', Auth::id())
            ->whereIn('status', [
                RevolvingFundStatus::APPROVED->value,
                RevolvingFundStatus::RELEASED->value,
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('revolvingFund', function (Builder $query): void {
                $query->where('user_id', Auth::id());
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('revolving_fund_id')
                    ->label('Revolving Fund')
                    ->relationship(
                        name: 'revolvingFund',
                        titleAttribute: 'fund_code',
                        modifyQueryUsing: fn(Builder $query) => $query
                            ->where('user_id', Auth::id())
                            ->whereIn('status', [
                                RevolvingFundStatus::APPROVED->value,
                                RevolvingFundStatus::RELEASED->value,
                            ]),
                    )
                    ->preload()
                    ->required()
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('Auto-assigned from your revolving fund.')
                    ->default(fn() => self::eligibleRevolvingFundQuery()->value('id')),

                TextInput::make('initial_amount')
                    ->label('Revolving Fund Amount')
                    ->numeric()
                    ->readOnly()
                    ->prefix('PHP')
                    ->default(fn() => self::eligibleRevolvingFundQuery()->value('initial_amount'))
                    ->required(),

                TextInput::make('remaining_amount')
                    ->label('Remaining Amount')
                    ->numeric()
                    ->readOnly()
                    ->prefix('PHP')
                    ->default(fn() => self::eligibleRevolvingFundQuery()->value('remaining_amount'))
                    ->required(),

                TextInput::make('total_amount')
                    ->label('Total')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->readOnly()
                    ->prefix('PHP'),

                Repeater::make('replenishmentItems')
                    ->label('Expense Items')
                    ->relationship('replenishmentItems')
                    ->live(onBlur: true)
                    ->afterStateHydrated(fn(Set $set, Get $get, ?array $state) => ReplenishmentService::getClosure($set, $get, $state))
                    ->afterStateUpdated(fn(Set $set, Get $get, ?array $state) => ReplenishmentService::getClosure($set, $get, $state))
                    ->schema([
                        TextInput::make('expense_name')
                            ->label('Expense')
                            ->required(),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('PHP'),

                        \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('attachment')
                            ->label('Attachment')
                            ->collection('attachments')
                            ->required()
                            ->multiple()
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('revolvingFund.fund_code')
                    ->label('Fund Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('initial_amount')
                    ->label('Revolving Fund Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Remaining Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListReplenishments::route('/'),
            'create' => Pages\CreateReplenishment::route('/create'),
            'view' => Pages\ViewReplenishment::route('/{record}'),
            'edit' => Pages\EditReplenishment::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return self::eligibleRevolvingFundQuery()->exists();
    }
}
