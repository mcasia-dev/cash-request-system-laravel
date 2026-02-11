<?php
namespace App\Filament\Resources;

use App\Enums\User\AccountStatus;
use App\Enums\User\Status;
use App\Filament\Resources\UserApprovalResource\Pages;
use App\Jobs\ApproveUserRegistrationJob;
use App\Jobs\RejectUserRegistrationJob;
use App\Models\User;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserApprovalResource extends Resource
{
    protected static ?string $model           = User::class;
    protected static ?string $navigationGroup = 'Administrator';
    protected static ?string $slug            = 'user-request-approval';
    protected static ?string $navigationLabel = 'User Request (For Approval)';
    protected static ?string $label           = 'User Request (For Approval)';
    protected static ?string $navigationIcon  = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('department_id', Auth::user()->department_id)
            ->where('status', Status::PENDING->value)
            ->count();

        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('department_id', Auth::user()->department_id)
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
                TextColumn::make('control_no')
                    ->label('Control No.')
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
                    ->label('Contact Number')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('department.department_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('signature_number')
                    ->label('Signature Number')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('account_status')
                    ->label('Account Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        AccountStatus::SUSPENDED->value => 'warning',
                        AccountStatus::ACTIVE->value    => 'success',
                        AccountStatus::BLOCKED->value   => 'danger',
                        default                         => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Approval Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Status::PENDING->value     => 'warning',
                        Status::APPROVED->value    => 'success',
                        Status::DISAPPROVED->value => 'danger',
                        default                    => 'secondary',
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
                ActionGroup::make([
                    ViewAction::make(),

                    Action::make('Approve')
                        ->visible(fn($record) => $record->status === Status::PENDING->value)
                        ->icon('heroicon-o-check-circle')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            $approver       = Auth::user();
                            $previousStatus = $record->status;

                            $record->update([
                                'status'    => Status::APPROVED->value,
                                'review_by' => $approver->id,
                                'review_at' => now(),
                            ]);

                            activity()
                                ->causedBy($approver)
                                ->performedOn($record)
                                ->event('approved')
                                ->withProperties([
                                    'previous_status' => $previousStatus,
                                    'new_status'      => Status::APPROVED->value,
                                    'review_by'       => $approver->id,
                                ])
                                ->log("User {$record->name} was approved by {$approver->name} ({$approver->position})");

                            ApproveUserRegistrationJob::dispatch($record);

                            Notification::make()
                                ->title('User Approved!')
                                ->success()
                                ->send();
                        })
                        ->successRedirectUrl(route('filament.admin.resources.user-request-approval.index')),

                    Action::make('Reject')
                        ->color('danger')
                        ->visible(fn($record) => $record->status === Status::PENDING->value)
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('reason_for_rejection')
                                ->label('Reason for Rejection')
                                ->required()
                                ->maxLength(65535),
                        ])
                        ->action(function (User $record, array $data) {
                            $approver       = Auth::user();
                            $previousStatus = $record->status;

                            $record->update([
                                'status'               => Status::DISAPPROVED->value,
                                'review_by'            => $approver->id,
                                'review_at'            => now(),
                                'reason_for_rejection' => $data['reason_for_rejection'],
                            ]);

                            activity()
                                ->causedBy($approver)
                                ->performedOn($record)
                                ->event('disapproved')
                                ->withProperties([
                                    'previous_status' => $previousStatus,
                                    'new_status'      => Status::DISAPPROVED->value,
                                    'review_by'       => $approver->id,
                                    'reason'          => $data['reason_for_rejection'],
                                ])
                                ->log("User {$record->name} was disapproved by {$approver->name} ({$approver->position})");

                            RejectUserRegistrationJob::dispatch($record);

                            Notification::make()
                                ->title('User Rejected!')
                                ->danger()
                                ->send();
                        })
                        ->successRedirectUrl(route('filament.admin.resources.user-request-approval.index')),
                ]),
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
            'index'  => Pages\ListUserApprovals::route('/'),
            'create' => Pages\CreateUserApproval::route('/create'),
            'edit'   => Pages\EditUserApproval::route('/{record}/edit'),
            'view'   => Pages\ViewUserApproval::route('/{record}/view'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $model): bool
    {
        return false;
    }

    public static function canDelete(Model $model): bool
    {
        return false;
    }
}
