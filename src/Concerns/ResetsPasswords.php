<?php

namespace Slides\Connector\Auth\Concerns;

use Illuminate\Http\Request;
use Slides\Connector\Auth\Facades\AuthService;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Auth\ResetsPasswords as BaseResetsPassword;

/**
 * Trait SendsPasswordResetEmails
 *
 * @package Slides\Connector\Auth\Concerns
 */
trait ResetsPasswords
{
    use BaseResetsPassword;

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param string $token
     * @param string $email
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(string $token, string $email)
    {
        if(!$email = AuthService::validatePasswordResetToken($token, $email)) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $email]
        );
    }

    /**
     * Reset user's password
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        $this->validate($request, $this->rules(), $this->validationErrorMessages());

        if(AuthService::resetPassword(
            $request->input('token'),
            $email = $request->input('email'),
            $password = $request->input('password'),
            $request->input('password_confirmation')
        )) {
            if(AuthService::login($email, $password)) {
                return $this->sendResetResponse(PasswordBroker::PASSWORD_RESET);
            }
        }

        return $this->sendResetFailedResponse($request, PasswordBroker::INVALID_PASSWORD);
    }
}