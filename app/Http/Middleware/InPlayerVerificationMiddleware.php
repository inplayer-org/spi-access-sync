<?php

namespace App\Http\Middleware;

use Closure;

class InPlayerVerificationMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle( $request, Closure $next )
    {
        if ($request->headers->has('X_INPLAYER_SIGNATURE'))
        {
            $token = $request->header('X_INPLAYER_SIGNATURE');

            if ($this->verifySignature(http_build_query($request->all()), $token))
            {
                return $next($request);
            }
        }

        return response('Forbidden access', 403);
    }

    private function verifySignature( $content, $signature )
    {
        $localSignature = 'sha256=' . hash_hmac('sha256', $content, env('SECRET_KEY'));

        return hash_equals($signature, $localSignature);
    }
}
