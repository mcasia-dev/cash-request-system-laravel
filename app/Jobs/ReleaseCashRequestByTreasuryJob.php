<?php
namespace App\Jobs;

use Illuminate\Support\Facades\Mail;
use App\Enums\CashRequest\StatusRemarks;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\ReleaseCashRequestByTreasuryMail;

class ReleaseCashRequestByTreasuryJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public $record)
    {
        $this->record = $record;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->record->user->email)->send(new ReleaseCashRequestByTreasuryMail($this->record));

         // Update the status once the email is sent.
        $this->record->status_remarks = StatusRemarks::FOR_LIQUIDATION->value;
        $this->record->save();
    }
}
