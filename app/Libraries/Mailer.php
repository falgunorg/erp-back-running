<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Mail;

class Mailer {

    public static function send_email_raw($message_data, $to, $subject, $from, $attachments = null) {

        $maildata = [];
        $maildata['MAIL_BODY'] = $message_data;

        Mail::send('emails.empty', $maildata, function($msg) use ($to, $subject, $from, $attachments) {
            $msg->to([$to]);
            $msg->from([$from]);
            $msg->subject($subject);
            $msg->replyTo($from);
            $msg->sender($from);
            if ($attachments) {
                if (!is_array($attachments)) {
                    $attachments = [$attachments];
                }
                foreach ($attachments as $attachment) {
                    $msg->attach($attachment, []);
                }
            }
        });
        return true;
    }

    public static function send_email($template_identifier, $data, $to, $subject, $from, $reply_to = null, $sender = null, $attachments = null) {
//        try {
//            $language_code = \App\Models\Player::where('player_email', $to)->pluck('player_language')->first();
////            $language_code = \App\Models\ContentLanguage::find($language_id)->language_code;
//        } catch (\Throwable $e) {
//            
//        }
//        if (!$language_code) {
//            $language_code = \App::getLocale();
//        }
        $template = \App\Models\EmailTemplate::where('template_id', $template_identifier)->first();
//        $template_detail = \App\Models\EmailTemplateDetail::where('email_template_id', $template->id)->where('language', $language_code)->first();
        $template_detail = \App\Models\EmailTemplateDetail::where('email_template_id', $template->id)->first();
        if (!$subject) {
            $subject = @$template_detail->title;
        }

        $message_data = @$template_detail->message;
        foreach ($data as $key => $val) {
            $message_data = str_replace('{{$' . $key . '}}', $val, $message_data);
        }

        //Do not delete. Experimental feature
//        $message = view('emails.' . $language_code . '.' . $template_identifier, $data)->render();

//        $THEME_DIR = \Route::current()->controller->THEME_DIR;

        $maildata = [];
        $maildata['MAIL_BANNER'] = $template->banner;
        $maildata['SUBJECT'] = $subject;
        $maildata['MAIL_BODY'] = $message_data;
        $maildata['HOME_LINK'] = env('APP_URL');
        
        if($template_identifier == 'custom_message'){
            $maildata['CONTENT_ALIGNMENT'] = 'left';
        } else {
            $maildata['CONTENT_ALIGNMENT'] = 'center';
        }

        if ($template->mail_type == 'Newsletter Email') {
            $player = \App\Models\Player::where('player_email', $to)->first();
            if ($player) {
                $transaction_code = md5($player->player_email . time());
                $newsletter_code = base64_encode($player->player_email . '-' . $player->id);
                $webvesion_mail = new \App\Models\WebversionMail;
                $webvesion_mail->player_id = $player->id;
                $webvesion_mail->template_id = $template_identifier;
                $webvesion_mail->subject = $subject;
                $webvesion_mail->transaction_code = $transaction_code;
                $webvesion_mail->message_data = $message_data;
                $webvesion_mail->save();

                $maildata['WEBVERSION_LINK'] = route('webversion_mail', ['code' => $transaction_code]);
                $maildata['UNSUBSCRIBE_LINK'] = route('unsubscribe_newsletter', ['code' => $newsletter_code]);
            }
        }
        
        if ($template and $template_detail) {
            return Mail::send('emails.generic', $maildata, function($msg) use ($to, $subject, $from, $reply_to, $sender, $attachments) {
                        $msg->to([$to]);
                        $msg->from([$from]);
                        $msg->subject($subject);
                        $msg->replyTo($reply_to);
                        $msg->sender($sender);
                        if ($attachments) {
                            if (!is_array($attachments)) {
                                $attachments = [$attachments];
                            }
                            foreach ($attachments as $attachment) {
                                $msg->attach($attachment, []);
                            }
                        }
                    });
        }

        return false;
    }

    public static function send_email_from_admin($template, $data, $to, $subject = null) {
        // $sender_email = env('ADMIN_SITE_EMAIL');
        $sender_email = 'support-team@igamingfeed.com';
        return self::send_email($template, $data, $to, $subject, $sender_email, $sender_email, $sender_email);
    }

    public static function send_email_from_admin_payment($template, $data, $to, $subject = null) {
        $sender_email = env('ADMIN_PAYMENT_EMAIL');
        return self::send_email($template, $data, $to, $subject, $sender_email, $sender_email, $sender_email);
    }

}
