<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CashRequest;
use Filament\Resources\Resource;
use App\Enums\CashRequest\Status;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use App\Filament\Resources\ForApprovalRequestResource\Pages;

class ForApprovalRequestResource extends Resource
{
    protected static ?string $model           = CashRequest::class;
    protected static ?string $navigationGroup = 'Administrator';
    protected static ?string $slug            = 'for-approval-requests';
    protected static ?string $navigationLabel = 'For Approval Requests';
    protected static ?string $label           = 'For Approval Requests';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        // ->whereHas('roles', function ($query) {
        //     $query->where('name', 'User');
        // })
            ->where('status', Status::PENDING->value);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('attachment')
                    ->collection('attachments'),

                TextColumn::make('request_no')
                    ->label('Request No.')
                    ->sortable()
                    ->searchable()
                    ->url(fn($record) => route('filament.admin.resources.for-approval-requests.view', $record)),

                TextColumn::make('user.name')
                    ->label('Requestor')
                    ->searchable(),

                TextColumn::make('activity_name')
                    ->label('Activity Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('activity_date')
                    ->label('Activity Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('nature_of_request')
                    ->label('Nature of Request')
                    ->sortable()
                    ->badge(),

                TextColumn::make('requesting_amount')
                    ->label('Requesting Amount')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->searchable(),

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
                Tables\Actions\ViewAction::make(),
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
            'index'  => Pages\ListForApprovalRequests::route('/'),
            'create' => Pages\CreateForApprovalRequest::route('/create'),
            'edit'   => Pages\EditForApprovalRequest::route('/{record}/edit'),
            'view'   => Pages\ViewForApprovalRequest::route('/{record}/view'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
