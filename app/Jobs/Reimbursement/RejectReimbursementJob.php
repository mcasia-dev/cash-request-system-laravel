<?php

namespace App\Jobs\Reimbursement;

use App\Mail\Reimbursement\RejectReimbursementMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class RejectReimbursementJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public $record)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->record->payee?->email) {
            return;
        }

        Mail::to($this->record->payee->email)->send(new RejectReimbursementMail($this->record));
    }
}

