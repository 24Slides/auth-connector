<?php

namespace Slides\Connector\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Guard as GuardContract;

/**
 * Class AuthService
 *
 * @package Slides\Connector\Auth
 */
class AuthService
{
    const HANDLER_USER_CREATE      = 'create';
    const HANDLER_USER_UPDATE      = 'update';
    const HANDLER_USER_DELETE      = 'delete';
    const HANDLER_USER_SYNC_CREATE = 'sync.create';
    const HANDLER_USER_SYNC_UPDATE = 'sync.update';
    const HANDLER_USER_SYNC_DELETE = 'sync.delete';

    /**
     * The class with handler methods
     *
     * @var object
     */
    protected $handlersContainer;

    /**
     * HTTP Client
     *
     * @var Client
     */
    protected $client;

    /**
     * The authentication guard.
     *
     * @var TokenGuard
     */
    protected $guard;

    /**
     * The fallback authentication guard.
     *
     * @var GuardContract
     */
    protected $fallbackGuard;

    /**
     * Checks whether a service is disabled
     *
     * @return bool
     */
    public function disabled(): bool
    {
        return !config('connector.auth.enabled', true);
    }

    /**
     * AuthService constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Authenticate a user.
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     *
     * @return mixed
     *
     * @throws
     */
    public function login(string $email, string $password, bool $remember = false)
    {
        if($this->disabled()) {
            return $this->handleFallback('login', compact('email', 'password', 'remember'));
        }

        return $this->guard->login($email, $password, $remember);
    }

    /**
     * Authenticate a user without the password.
     *
     * Warning! This method has implemented temporarily to make able to login users
     * who use Social Auth on 24Templates. MUST NOT be used in any other cases.
     *
     * @param string $email
     * @param bool $remember
     *
     * @return mixed
     *
     * @throws
     */
    public function unsafeLogin(string $email, bool $remember = false)
    {
        if($this->disabled()) {
            return $this->handleFallback('unsafeLogin', compact('email', 'remember'));
        }

        return $this->guard->unsafeLogin($email, $remember);
    }

    /**
     * Logout a user.
     *
     * @return mixed
     *
     * @throws
     */
    public function logout()
    {
        if($this->disabled()) {
            return $this->handleFallback('logout');
        }

        $this->guard->logout();

        return null;
    }

    /**
     * Create a remote user.
     *
     * @param int $userId
     * @param string $name
     * @param string $email
     * @param string $password
     *
     * @return array
     */
    public function register(int $userId, string $name, string $email, string $password)
    {
        if($this->disabled()) {
            return [];
        }

        return $this->client->request('register', compact('userId', 'name', 'email', 'password'));
    }

    /**
     * Send an email with a password resetting link
     *
     * @param string $email
     *
     * @return bool
     *
     * @throws
     */
    public function forgot(string $email)
    {
        if($this->disabled()) {
            return $this->handleFallback('forgot', compact('email'));
        }

        $this->client->request('forgot', compact('email'));

        return $this->client->success(true);
    }

    /**
     * Checks whether password reset token is valid
     *
     * @param string $token
     * @param string $email
     *
     * @return string|false
     *
     * @throws
     */
    public function validatePasswordResetToken(string $token, string $email)
    {
        if($this->disabled()) {
            return $this->handleFallback('validateReset', compact('token', 'email'));
        }

        $response = $this->client->request('validateReset', compact('token', 'email'));

        if(!$this->client->success(true)) {
            return false;
        }

        return array_get($response, 'user.email');
    }

    /**
     * Checks whether password reset token is valid
     *
     * @param string $token
     * @param string $email
     * @param string $password
     * @param string $confirmation
     *
     * @return array|false
     *
     * @throws
     */
    public function resetPassword(string $token, string $email, string $password, string $confirmation)
    {
        $parameters = compact('token', 'email', 'password', 'confirmation');

        if($this->disabled()) {
            return $this->handleFallback('resetPassword', $parameters);
        }

        $response = $this->client->request('reset', $parameters);

        if(!$this->client->success(true)) {
            return false;
        }

        return $response;
    }

    /**
     * Update a remote user
     *
     * @param int $id Local user ID
     * @param string|null $name
     * @param string|null $email
     * @param string|null $password Raw password, in case if changed
     *
     * @return array|false
     */
    public function update(int $id, ?string $name, ?string $email, ?string $password)
    {
        if($this->disabled()) {
            return false;
        }

        $attributes = array_filter(compact('id', 'name', 'email', 'password'));

        $response = $this->client->request('update', compact('id', 'attributes'));

        if(!$this->client->success(true)) {
            return false;
        }

        return $response;
    }

    /**
     * Load handlers from the given container.
     *
     * @param $container
     *
     * @return void
     */
    public function loadHandlers($container)
    {
        $this->handlersContainer = $container;
    }

    /**
     * Run a handler
     *
     * @param string $key
     * @param array $parameters
     * @param \Closure|null $fallback
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function handle(string $key, array $parameters = [], \Closure $fallback = null)
    {
        $handler = camel_case(str_replace('.', ' ', $key));

        if(!method_exists($this->handlersContainer, $handler)) {
            throw new \InvalidArgumentException("Handler `{$handler}` cannot be found");
        }

        return $this->ensure(function() use ($handler, $parameters) {
            return call_user_func_array([$this->handlersContainer, $handler], $parameters);
        }, $fallback);
    }

    /**
     * Run a fallback handler
     *
     * @param string $key
     * @param array $parameters
     * @param \Closure|null $fallback
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function handleFallback(string $key, array $parameters = [], \Closure $fallback = null)
    {
        $key = 'fallback' . studly_case($key);
        $parameters = array_merge(['guard' => $this->fallbackGuard], $parameters);

        return $this->handle($key, $parameters, $fallback);
    }

    /**
     * Performs a callback logic within database transaction.
     *
     * @param \Closure $callback
     * @param \Closure|null $fallback The callback which should fired when exception throws.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function ensure(\Closure $callback, \Closure $fallback = null)
    {
        DB::beginTransaction();

        try {
            $output = $callback();
        }
        catch(\Exception $e) {
            DB::rollBack();

            if(is_null($fallback)) {
                throw $e;
            }

            $output = $fallback($e);
        }

        DB::commit();

        return $output;
    }

    /**
     * Set authentication guard.
     *
     * @param \Slides\Connector\Auth\TokenGuard $guard
     */
    public function setGuard(TokenGuard $guard): void
    {
        $this->guard = $guard;
    }

    /**
     * Set fallback authentication guard.
     *
     * @param GuardContract $guard
     */
    public function setFallbackGuard(GuardContract $guard): void
    {
        $this->fallbackGuard = $guard;
    }

    /**
     * Set HTTP Client.
     *
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Get HTTP client.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}