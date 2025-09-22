<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable {

    use Queueable,
        SerializesModels;

    public $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function build() {

        $email = $this->from($this->data['from_email'])
                ->subject($this->data['subject'])
                ->view('emails.template')
                ->with([
            'subject' => $this->data['subject'], // Pass the subject to the view
            'body' => $this->data['body'],
        ]);

//        
//        $email = $this->subject($this->data['subject'])
//                ->view('emails.template') // Create a blade template in resources/views/emails/template.blade.php
//                ->with([
//            'subject' => $this->data['subject'], // Pass the subject to the view
//            'body' => $this->data['body'],
//        ]);
        // Attach files if present
        if (isset($this->data['attachments'])) {
            foreach ($this->data['attachments'] as $file) {
                $email->attach($file->getPathname(), [
                    'as' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                ]);
            }
        }

        return $email;
    }

}
