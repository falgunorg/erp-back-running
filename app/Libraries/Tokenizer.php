<?php

namespace App\Libraries;

use App\Libraries\CustomString;
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Tokenizer {

    private static $tokenKey;
    private static $idKey;
    private static $encryptionMethod;

    public static function __callStatic($methodName, $injectedArguments) {
        if (empty(self::$tokenKey)) {
            self::$tokenKey = env('TOKEN_ENCRYPTION_KEY', '(I&%4&^%UWBdf8yfw6&%43%7R');
            self::$idKey = env('ID_ENCRYPTION_KEY', '^tgFEd$334)68');
            self::$encryptionMethod = "AES-256-CBC";
        }
        return call_user_func_array(__CLASS__ . '::' . $methodName, $injectedArguments);
    }

    protected static function encrypt($data, $expiry = null) {

        if (!intval($expiry)) {
            $expiry = 360;
        }
        if (!$data) {
            $data = [];
        }

        $data = (object) $data;

        $token['data'] = $data;
        $token['exp'] = time() + $expiry;
        $jwt = JWT::encode($token, self::$tokenKey, "HS256");
        return $jwt;
    }

    protected static function validateToken($token) {
        try {
//            file_put_contents("files.txt", $token);

            $user = JWT::decode($token, new Key(self::$tokenKey, 'HS256'));
//            $user = JWT::decode($token, self::$tokenKey, 'HS256');
//            if ($user) {
//                $iv = substr($user->id, 0, 16);
//                $user->id = substr($user->id, 16);
//                $user->id = openssl_decrypt($user->id, self::$encryptionMethod, self::$idKey, 0, $iv);
//            }
//            file_put_contents("files.txt", print_r($user, true));
            return $user;
        } catch (\Exception $e) {
//            file_put_contents("files.txt", $e->getMessage());
            return null;
        }
    }

    protected static function password($plain_pass = "") {
        $encryptedPass = base64_encode(hash_hmac('sha256', trim($plain_pass), self::$idKey, true) . hash_hmac('sha1', trim($plain_pass), self::$idKey, true));
        return substr($encryptedPass, 0, 255);
    }

}
