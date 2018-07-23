<?php

namespace Slides\Connector\Auth\Concerns;

use Illuminate\Http\Request;
use Slides\Connector\Auth\Facades\AuthService;
use Illuminate\Foundation\Auth\AuthenticatesUsers as BaseAuthenticatesUsers;

/**
 * Trait AuthenticatesUsers
 *
 * @package Slides\Connector\Auth\Concerns
 */
trait AuthenticatesUsers
{
    use BaseAuthenticatesUsers;

    /**
     * Attempt to log the user into the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return AuthService::login(
            $request->input($this->username()),
            $request->input('password'),
            $request->has('remember')
        );
    }
}