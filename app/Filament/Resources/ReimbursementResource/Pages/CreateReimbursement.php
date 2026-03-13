<?php

namespace App\Filament\Resources\ReimbursementResource\Pages;

use App\Filament\Resources\ReimbursementResource;
use App\Jobs\Reimbursement\SubmitReimbursementForApprovalJob;
use App\Services\Reimbursement\ForApprovalReimbursementService;
use App\Services\Reimbursement\ReimbursementApprovalFlowService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateReimbursement extends CreateRecord
{
    protected static string $resource = ReimbursementResource::class;

    protected function afterCreate(): void
    {
        $record = $this->getRecord()->fresh(['payee']);
        $user = Auth::user();

        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('submitted')
            ->withProperties([
                'reimbursement_no' => $record->reimbursement_no,
                'total_amount' => $record->total_amount,
                'mode_of_request' => $record->reimbursementMode?->name,
                'status' => $record->status,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Reimbursement {$record->reimbursement_no} was submitted by {$user->name} ({$user->position})");

        app(ReimbursementApprovalFlowService::class)->initializeApprovals($record);
        app(ForApprovalReimbursementService::class)->notifyCurrentApprovers($record);

        SubmitReimbursementForApprovalJob::dispatch($record->id);
    }
}
