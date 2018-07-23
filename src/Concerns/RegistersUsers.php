<?php

namespace Slides\Connector\Auth\Concerns;

use Illuminate\Http\Request;
use Slides\Connector\Auth\Facades\AuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers as BaseRegistersUsers;

/**
 * Trait RegistersUsers
 *
 * @package Slides\Connector\Auth\Concerns
 */
trait RegistersUsers
{
    use BaseRegistersUsers;

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        AuthService::login(
            $request->input($this->username()),
            $request->input('password'),
            false
        );

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     *
     * @return \App\User
     */
    protected function create(array $data)
    {
        return AuthService::handle('create', [
            'attributes' => array_only($data, ['name', $this->username(), 'password'])
        ]);
    }
}