<?php

namespace App\Filament\Resources\UserApprovalResource\Pages;

use App\Enums\User\AccountStatus;
use App\Enums\User\Status;
use App\Filament\Resources\UserApprovalResource;
use App\Models\User;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUserApproval extends ViewRecord
{
    protected static string $resource = UserApprovalResource::class;

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
