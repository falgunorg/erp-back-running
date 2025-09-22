<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model {

    public $table = 'accesstokens';

    public static function saveToken($request, $userId) {
        $userAgent = addslashes($_SERVER['HTTP_USER_AGENT']);
               
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {   //check ip from share internet
            $ip_str = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
            $ip_str = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip_str = $_SERVER['REMOTE_ADDR'];
        }
        
        $token = new self();
        $token->user_id = $userId;
//        $token->ip = \App\Libraries\IpParser::get_user_ip();
        $token->ip = $ip_str;
        $token->user_agent = $userAgent;
        $token->token = \App\Libraries\RandomString::gen_code(64,128);
        $token->expires_at = date('Y-m-d H:i:s', strtotime('+ 10 days'));
        $token->save();
        return $token;
    }

}
