<?php

namespace App\Filament\Resources;

use App\Enums\NatureOfRequestEnum;
use App\Filament\Resources\ApprovalRuleResource\Pages;
use App\Models\ApprovalRule;
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

class ApprovalRuleResource extends Resource
{
    protected static ?string $model           = ApprovalRule::class;
    protected static ?string $navigationGroup = 'Administrator';
    protected static ?string $slug            = 'approval-rules';
    protected static ?string $navigationLabel = 'Approval Rules';
    protected static ?string $label           = 'Approval Rule';
    protected static ?string $pluralLabel     = 'Approval Rules';
    protected static ?string $navigationIcon  = 'heroicon-o-adjustments-horizontal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('nature')
                    ->label('Nature of Request')
                    ->options(NatureOfRequestEnum::filamentOptions())
                    ->required(),

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

                Repeater::make('approvalRuleSteps')
                    ->label('Approver Roles')
                    ->relationship()
                    ->defaultItems(1)
                    ->addActionLabel('Add Role')
                    ->schema([
                        Select::make('role_name')
                            ->label('Approver Role')
                            ->options([
                                'department_head'        => 'Department Head',
                                'president'              => 'President',
                                'sales_channel_manager'  => 'Sales Channel Manager',
                                'national_sales_manager' => 'National Sales Manager',
                                'treasury_manager'       => 'Treasury Manager',
                                'treasury_supervisor'    => 'Treasury Supervisor',
                            ])
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->searchable(),
                    ])
                    ->columns(1)
                    ->grid(1)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nature')
                    ->label('Nature')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('min_amount')
                    ->label('Min Amount')
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'PHP ' . number_format((float) $state, 2)),

                TextColumn::make('max_amount')
                    ->label('Max Amount')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state === null ? 'No Limit' : 'PHP ' . number_format((float) $state, 2)),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('F d, Y H:i A')
                    ->timezone('Asia/Manila')
                    ->sortable(),
            ])
            ->defaultSort('nature')
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
            'index'  => Pages\ListApprovalRules::route('/'),
            'create' => Pages\CreateApprovalRule::route('/create'),
            'edit'   => Pages\EditApprovalRule::route('/{record}/edit'),
        ];
    }
}
