<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StoreReportMail extends Mailable {

    use Queueable,
        SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $reportData;

    public function __construct($reportData, $username) {
        $this->reportData = $reportData;
        $this->username = $username;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                        ->subject('Store Report')
                        ->view('emails.store-report')
                        ->with(['reportData' => $this->reportData, 'username' => $this->username]);
    }

}
