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
        // Only protected POST, PUT, PATCH, and DELETE routes
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Check the `X-DS-Chompy-API-Key` header for authorization.
        if ($request->header('X-DS-Chompy-API-Key') !== config('app.api_key')) {
            throw new AuthenticationException;
        }

        return $next($request);
    }
}
