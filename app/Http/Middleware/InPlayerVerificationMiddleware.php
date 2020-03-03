<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

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
        Log::info('Request received: ' . $request->getContent());

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
        $id = $content['resource']['description'] ?? null;
        $secretKey = config("inplayer.$id.secret_key", config('inplayer.secret_key'));

        $localSignature = 'sha256=' . hash_hmac('sha256', $content, $secretKey);
        return hash_equals($signature, $localSignature);
    }
}
