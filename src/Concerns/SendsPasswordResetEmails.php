<?php

namespace Slides\Connector\Auth\Concerns;

use Illuminate\Http\Request;
use Slides\Connector\Auth\Facades\AuthService;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails as BaseSendsPasswordResetEmails;

/**
 * Trait SendsPasswordResetEmails
 *
 * @package Slides\Connector\Auth\Concerns
 */
trait SendsPasswordResetEmails
{
    use BaseSendsPasswordResetEmails;

    /**
     * Send a reset link to the given user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        return AuthService::forgot($request->input('email'))
            ? back()->with('status', 'We have e-mailed your password reset link!')
            : back()->withErrors(['email' => 'We can\'t find a user with that e-mail address.']);
    }
}