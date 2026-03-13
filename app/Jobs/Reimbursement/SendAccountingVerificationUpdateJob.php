<?php

namespace App\Jobs\Reimbursement;

use App\Mail\Reimbursement\AccountingVerificationUpdateMail;
use App\Models\Reimbursement\Reimbursement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAccountingVerificationUpdateJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $reimbursementId,
        public array $emails,
        public string $subjectLine,
        public string $messageBody,
        public string $actionUrl,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $record = Reimbursement::query()->with('payee')->find($this->reimbursementId);

        if (! $record || empty($this->emails)) {
            return;
        }

        foreach ($this->emails as $email) {
            if (! filled($email)) {
                continue;
            }

            Mail::to($email)->send(new AccountingVerificationUpdateMail(
                record: $record,
                subjectLine: $this->subjectLine,
                messageBody: $this->messageBody,
                actionUrl: $this->actionUrl,
            ));
        }
    }
}

