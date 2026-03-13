<?php

namespace App\Jobs\CashRequest;

use App\Enums\CashRequest\StatusRemarks;
use App\Mail\CashRequest\ApproveCashRequestByTreasuryMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class ApproveCashRequestByTreasuryJob implements ShouldQueue
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
        Mail::to($this->record->user->email)->send(new ApproveCashRequestByTreasuryMail($this->record));

        // Update the status once the email is sent.
        $this->record->status_remarks = StatusRemarks::FOR_RELEASING->value;
        $this->record->save();
    }
}
