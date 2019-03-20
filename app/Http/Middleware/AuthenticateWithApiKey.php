<?php

namespace Chompy\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;

class AuthenticateWithApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        // Check the `X-DS-Importer-API-Key` header for authorization.
        if ($request->header('X-DS-Importer-API-Key') !== config('app.api_key')) {
            throw new AuthenticationException;
        }

        return $next($request);
    }
}
