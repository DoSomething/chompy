<?php

namespace Chompy\Http\Controllers\Auth;

use Chompy\Http\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Where to redirect users after logout.
     *
     * @var string
     */
    protected $redirectAfterLogout = '/';

    /**
     * Handle a login request to the application.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getLogin(ServerRequestInterface $request, ResponseInterface $response)
    {
        return gateway('northstar')->authorize($request, $response, $this->redirectTo);
    }

    /**
     * Handle a logout request to the application.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getLogout(ResponseInterface $response)
    {
        return gateway('northstar')->logout($response, $this->redirectAfterLogout);
    }
}
