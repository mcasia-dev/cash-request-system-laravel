<?php

namespace App\Filament\Resources;

use App\Enums\CashRequest\ModeOfTransfer;
use App\Enums\CashRequest\Status;
use App\Enums\Reimbursement\StatusRemarks;
use App\Filament\Resources\ReimbursementResource\Pages;
use App\Models\Reimbursement\Reimbursement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ReimbursementResource extends Resource
{
    protected static ?string $model = Reimbursement::class;
    protected static ?string $navigationGroup = 'Reimbursements';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('payee_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('payee_id')
                    ->default(fn() => Auth::id())
                    ->dehydrated(true)
                    ->required(),

                Forms\Components\DatePicker::make('reimbursement_date')
                    ->label('Reimbursement Date')
                    ->required()
                    ->minDate(now()->toDateString())
                    ->prefixIcon('heroicon-m-calendar')
                    ->native(false),

                Forms\Components\Select::make('reimbursement_mode_id')
                    ->label('Mode of Request')
                    ->relationship(
                        name: 'reimbursementMode',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query->where('is_active', true)->orderBy('name')
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('mode_of_transfer')
                    ->label('Mode of Transfer')
                    ->options(ModeOfTransfer::filamentOptions())
                    ->required(),

                Forms\Components\Textarea::make('purpose'),

                Forms\Components\TextInput::make('total_amount')
                    ->label('Total Amount')
                    ->required()
                    ->numeric()
                    ->prefix('PHP ')
                    ->readOnly()
                    ->dehydrated(true)
                    ->default(0.00),

                Forms\Components\Hidden::make('status_remarks')
                    ->default(StatusRemarks::REIMBURSEMENT_SUBMITTED->value),

                Forms\Components\Repeater::make('reimbursementItems')
                    ->label('Items to Reimburse')
                    ->relationship('reimbursementItems')
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columns(2)
                    ->columnSpanFull()
                    ->live()
                    ->afterStateHydrated(function (Set $set, ?array $state): void {
                        $set('total_amount', self::sumItemAmounts($state));
                    })
                    ->afterStateUpdated(function (Set $set, ?array $state): void {
                        $set('total_amount', self::sumItemAmounts($state));
                    })
                    ->schema([
                        Forms\Components\TextInput::make('item_name')
                            ->label('Item')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->prefix('PHP ')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $set('../../total_amount', self::sumItemAmounts($get('../../reimbursementItems')));
                            }),

                        Forms\Components\Textarea::make('description')
                            ->label('Description / Purpose')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\SpatieMediaLibraryFileUpload::make('attachment')
                            ->label('Attachment')
                            ->collection('attachments')
                            ->required()
                            ->multiple()
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reimbursement_no')
                    ->label('Reimbursement No.')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('reimbursement_date')
                    ->label('Reimbursement Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reimbursementMode.name')
                    ->label('Mode of Request')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mode_of_transfer')
                    ->label('Mode of Transfer')
                    ->sortable()
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        Status::PENDING->value => 'warning',
                        Status::IN_PROGRESS->value => 'secondary',
                        Status::REJECTED->value => 'danger',
                        Status::APPROVED->value => 'success',
                        Status::RELEASED->value => 'info',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('status_remarks')
                    ->label('Status Remarks')
                    ->sortable()
                    ->searchable()
                    ->badge(),

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
                Tables\Actions\Action::make('track_status')
                    ->label('Track Status')
                    ->color('warning')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn($record) => route('filament.admin.resources.reimbursements.track-status', ['record' => $record])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListReimbursements::route('/'),
            'create' => Pages\CreateReimbursement::route('/create'),
            'view' => Pages\ViewReimbursement::route('/{record}'),
            'edit' => Pages\EditReimbursement::route('/{record}/edit'),
            'track-status' => Pages\TrackReimbursementStatus::route('/{record}/track-status'),
        ];
    }

    public static function canEdit(Model $model): bool
    {
        return false;
    }

    public static function canDelete(Model $model): bool
    {
        return false;
    }

    private static function sumItemAmounts(?array $items): float
    {
        if (blank($items)) {
            return 0;
        }

        return collect($items)->sum(fn(array $item) => (float)($item['amount'] ?? 0));
    }
}
