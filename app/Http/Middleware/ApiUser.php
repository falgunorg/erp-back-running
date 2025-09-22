<?php

namespace App\Http\Middleware;

use Closure;
use App\Libraries\Tokenizer;

class ApiUser {

    public function handle($request, Closure $next) {
        $request->user = null;
        $token = $request->header('Authorization');
        if ($token) {
            $token = substr($token, strlen("Bearer "));
            $decrypted = Tokenizer::validateToken($token);
            if ($decrypted and $decrypted->data) {
                $request->user = $decrypted->data;
            }
        }
        return $next($request);
    }

}
