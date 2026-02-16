<?php
namespace App\Filament\Resources\UserApprovalResource\Pages;

use App\Models\User;
use App\Enums\User\Status;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use App\Enums\User\AccountStatus;
use Illuminate\Support\Facades\Auth;
use App\Jobs\RejectUserRegistrationJob;
use Filament\Forms\Components\Textarea;
use App\Jobs\ApproveUserRegistrationJob;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\UserApprovalResource;

class ViewUserApproval extends ViewRecord
{
    protected static string $resource = UserApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
                ->color('secondary')
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
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('User Details')
                    ->schema([
                        TextEntry::make('control_no')
                            ->label('Control No.'),

                        TextEntry::make('name')
                            ->label('Full Name'),

                        TextEntry::make('email')
                            ->label('Email'),

                        TextEntry::make('contact_number')
                            ->label('Mobile Number'),

                        TextEntry::make('position')
                            ->label('Position'),

                        TextEntry::make('signature_number')
                            ->label('Signature Number'),
                    ])
                    ->columns(2),

                Section::make('Department')
                    ->schema([
                        TextEntry::make('department.department_name')
                            ->label('Department'),

                        TextEntry::make('department.department_head')
                            ->label('Department Head'),
                    ])
                    ->columns(2),

                Section::make('Approval Status')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Approval Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                Status::PENDING->value     => 'warning',
                                Status::APPROVED->value    => 'success',
                                Status::DISAPPROVED->value => 'danger',
                                default                    => 'secondary',
                            }),

                        TextEntry::make('account_status')
                            ->label('Account Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                AccountStatus::SUSPENDED->value => 'warning',
                                AccountStatus::ACTIVE->value    => 'success',
                                AccountStatus::BLOCKED->value   => 'danger',
                                default                         => 'secondary',
                            }),

                        TextEntry::make('reviewer_name')
                            ->label('Reviewed By')
                            ->state(fn($record) => $record->review_by ? User::find($record->review_by)?->name : null)
                            ->placeholder('Not reviewed yet'),

                        TextEntry::make('review_at')
                            ->label('Reviewed At')
                            ->dateTime('F d, Y h:i A')
                            ->placeholder('Not reviewed yet'),

                        TextEntry::make('reason_for_rejection')
                            ->label('Reason for Rejection')
                            ->visible(fn($record) => $record->status === Status::DISAPPROVED->value)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Timestamps')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('F d, Y h:i A'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('F d, Y h:i A'),
                    ])
                    ->columns(2),
            ]);
    }
}
