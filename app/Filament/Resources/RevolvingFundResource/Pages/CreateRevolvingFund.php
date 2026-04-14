<?php

namespace App\Filament\Resources\RevolvingFundResource\Pages;

use App\Enums\RevolvingFund\Status;
use App\Filament\Resources\RevolvingFundResource;
use App\Models\RevolvingFund\RevolvingFund;
use App\Services\RevolvingFund\ForApprovalRevolvingFundService;
use App\Services\RevolvingFund\RevolvingFundApprovalFlowService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateRevolvingFund extends CreateRecord
{
    protected static string $resource = RevolvingFundResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = $data['user_id'] ?? Auth::id();

        $existingFund = RevolvingFund::query()
            ->where('user_id', $data['user_id'])
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', '!=', Status::REJECTED->value);
            })
            ->exists();

        if ($existingFund) {
            throw ValidationException::withMessages([
                'user_id' => 'This employee already has an existing revolving fund request.',
            ]);
        }

        $rule = app(RevolvingFundApprovalFlowService::class)->resolveRule((object)[
            'initial_amount' => $data['initial_amount'] ?? 0,
        ]);

        if (!$rule) {
            throw ValidationException::withMessages([
                'initial_amount' => 'No active revolving fund approval rule found. Please configure one in Revolving Fund Approval Rules first.',
            ]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord()->fresh(['addedBy', 'user']);
        $user = Auth::user();

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('submitted')
            ->withProperties([
                'fund_code' => $record->fund_code,
                'initial_amount' => $record->initial_amount,
                'remaining_amount' => $record->remaining_amount,
                'status' => $record->status,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Revolving fund request {$record->fund_code} was submitted by {$user->name} ({$user->position})");

        app(RevolvingFundApprovalFlowService::class)->initializeApprovals($record);
        app(ForApprovalRevolvingFundService::class)->notifyCurrentApprovers($record, true);
    }
}
