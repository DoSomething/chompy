<?php

namespace Chompy\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;

class AuthenticateWithApiKey
{
    protected $headers = [
      'X-DS-Importer-API-Key' => config('app.callpower_key')),
      'X-DS-CallPower-API-Key' => config('app.callpower_key')),
      'X-DS-SoftEdge-API-Key' => config('app.softedge_key')),
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  $headers
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, $headers)
    {
      // Check the header for authorization.
      foreach ($headers as $header => $key) {
        if ($request->header($header) == $key) {
            return $next($request);
        }
      }

      throw new AuthenticationException;
    }
}
