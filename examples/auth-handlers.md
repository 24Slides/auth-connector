# Auth handlers based on default Laravel application

```php
<?php

namespace App\Services\Auth;

use App\User as User;
use Illuminate\Http\Request;
use Illuminate\Auth\SessionGuard;
use Slides\Connector\Auth\AuthService;
use Slides\Connector\Auth\Sync\User as SyncUser;

/**
 * Class AuthHandlers
 *
 * @package App\Services\Auth
 */
class AuthHandlers
{
    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * AuthHandlers constructor.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Create a user locally and remotely
     *
     * @param array $attributes
     *
     * @return User
     */
    public function create(array $attributes)
    {
        $user = User::create($attributes);

        $this->authService->register(
            $user->id,
            $user->name,
            $user->email,
            $attributes['password']
        );

        return $user;
    }

    /**
     * Update a user locally and remotely
     *
     * @param User $user
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Http\Models\User|\Illuminate\Database\Eloquent\Model
     */
    public function update(User $user, Request $request)
    {
        $attributes = $request->only('name', 'email');

        if($user->update($attributes)) {
            $this->authService->update($user->id, $user->name, $user->email, null);
        }

        return $user;
    }

    /**
     * Update a user's password locally and remotely
     *
     * @param User $user
     * @param string $password
     *
     * @return \App\Http\Models\User|\Illuminate\Database\Eloquent\Model
     */
    public function updatePassword(User $user, string $password)
    {
        if($user->update(['password' => \Hash::make($password)])) {
            $this->authService->update($user->id, null, null, $password);
        }

        return $user;
    }

    /**
     * Delete a user locally and remotely
     *
     * @param User $user
     *
     * @return void
     */
    public function delete(User $user)
    {
        // ...
    }

    /**
     * Create a user locally
     *
     * @param SyncUser $remote
     *
     * @return void
     */
    public function syncCreate(SyncUser $remote)
    {
        User::create([
            'name' => $remote->getName(),
            'email' => $remote->getEmail(),
            'password' => $remote->getPassword()
        ]);
    }

    /**
     * Update a local user
     *
     * @param SyncUser $remote
     * @param User $local
     *
     * @return void
     */
    public function syncUpdate(SyncUser $remote, User $local)
    {
        $local->update([
            'email' => $remote->getEmail(),
            'name' => $remote->getName(),
            'password' => $remote->getPassword(),
            'updated_at' => $remote->getUpdated()
        ]);
    }

    /**
     * Delete a local user
     *
     * @param SyncUser $remote
     * @param User $local
     *
     * @return void
     */
    public function syncDelete(SyncUser $remote, User $local)
    {
        // ...
    }

    /**
     * Login a user when remote service is disabled
     *
     * @param SessionGuard $guard
     * @param string $email
     * @param string $password
     * @param bool $remember
     *
     * @return bool
     */
    public function fallbackLogin(SessionGuard $guard, string $email, string $password, bool $remember = false)
    {
        return $guard->attempt(compact('email', 'password'), $remember);
    }

    /**
     * Login a user without the password when remote service is disabled
     *
     * Warning! This method has implemented temporarily to make able to login users
     * who use Social Auth on 24Templates. MUST NOT be used in any other cases.
     * 
     * @param SessionGuard $guard
     * @param string $email
     * @param bool $remember
     *
     * @return bool
     */
    public function fallbackUnsafeLogin(SessionGuard $guard, string $email, bool $remember = false)
    {
        $user = User::query()
            ->where('email', $email)
            ->first();
        
        if(!$user) {
            return false;
        }
        
        $guard->login($user, $remember);
        
        return true;
    }

    /**
     * Logout a user when remote service is disabled
     *
     * @param SessionGuard $guard
     *
     * @return void
     */
    public function fallbackLogout(SessionGuard $guard)
    {
        $guard->logout();
    }

    /**
     * Send a link with password resetting token.
     *
     * @param SessionGuard $guard
     * @param string $email
     *
     * @return bool
     */
    public function fallbackForgot(SessionGuard $guard, string $email)
    {
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->passwordBroker()->sendResetLink(compact('email'));

        return $response == \Illuminate\Support\Facades\Password::RESET_LINK_SENT;
    }

    /**
     * Send a link with password resetting token.
     *
     * @param SessionGuard $guard
     * @param string $email
     * @param string $token
     *
     * @return string|false
     */
    public function fallbackValidateReset(SessionGuard $guard, string $token, string $email)
    {
        $email = decrypt($email);

        if(!$user = $this->passwordBroker()->getUser(['email' => $email])) {
            return false;
        }

        return $this->passwordBroker()->getRepository()->exists($user, $token)
            ? $email
            : false;
    }

    /**
     * Send a link with password resetting token.
     *
     * @param SessionGuard $guard
     * @param string $email
     * @param string $token
     *
     * @return array|false
     */
    public function fallbackResetPassword(SessionGuard $guard, string $token, string $email, string $password, string $confirmation)
    {
        $email = decrypt($email);

        $credentials = compact('email', 'token', 'password');
        $credentials['password_confirmation'] = $confirmation;

        $result = $this->passwordBroker()->reset($credentials, function(User $user, string $password) {
            $user->update(['password' => $password]);
        });

        return $result === \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? ['user' => ['email' => $email]]
            : false;
    }

    /**
     * Retrieve the password broken
     *
     * @return \Illuminate\Auth\Passwords\PasswordBroker|\Illuminate\Contracts\Auth\PasswordBroker
     */
    private function passwordBroker()
    {
        return \Illuminate\Support\Facades\Password::broker();
    }
}
```