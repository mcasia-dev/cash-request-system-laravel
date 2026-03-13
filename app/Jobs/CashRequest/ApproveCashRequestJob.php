<?php

namespace App\Jobs\CashRequest;

use App\Mail\CashRequest\ApproveCashRequestMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class ApproveCashRequestJob implements ShouldQueue
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
        Mail::to($this->record->user->email)->send(new ApproveCashRequestMail($this->record));
    }
}
