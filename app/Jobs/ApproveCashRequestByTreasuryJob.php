<?php
namespace App\Jobs;

use App\Mail\ApproveCashRequestMail;
use Illuminate\Support\Facades\Mail;
use App\Enums\CashRequest\StatusRemarks;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\ApproveCashRequestByTreasuryMail;

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
