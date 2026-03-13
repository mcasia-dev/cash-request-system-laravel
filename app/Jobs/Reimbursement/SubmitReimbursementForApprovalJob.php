<?php

namespace App\Jobs\Reimbursement;

use App\Mail\Reimbursement\ReimbursementSubmittedToDepartmentHeadMail;
use App\Models\Reimbursement\Reimbursement;
use App\Services\Reimbursement\ReimbursementApprovalFlowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SubmitReimbursementForApprovalJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $reimbursementId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $record = Reimbursement::query()
            ->with(['payee'])
            ->find($this->reimbursementId);

        if (! $record) {
            return;
        }

        $approvers = app(ReimbursementApprovalFlowService::class)->getCurrentPendingApprovers($record);

        foreach ($approvers as $approver) {
            if (! $approver->email) {
                continue;
            }

            Mail::to($approver->email)
                ->send(new ReimbursementSubmittedToDepartmentHeadMail($record, $approver));
        }
    }
}
