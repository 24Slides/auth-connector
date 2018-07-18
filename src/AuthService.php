<?php

namespace Slides\Connector\Auth;

use Illuminate\Support\Facades\DB;
use Slides\Connector\Auth\TokenGuard;
use Illuminate\Contracts\Auth\Guard as GuardContract;

/**
 * Class Service
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

            $output = $fallback();
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
}