<?php

namespace App\Http\Middleware;

use Closure;
use App\Libraries\Tokenizer;

class ApiAuthenticate {

    public function handle($request, Closure $next, $role = null) {
        $token = $request->bearerToken();

        if (!$token) {
            return response('Unauthorized.', 401);
        }

        $decrypted = Tokenizer::validateToken($token);

        if (!$decrypted || !$decrypted->data) {
            return response('Unauthorized.', 401);
        }

        $request->merge(['user' => $decrypted->data]);

        return $next($request);
    }

}
