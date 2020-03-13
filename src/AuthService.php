<?php

namespace Slides\Connector\Auth;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Guard as GuardContract;
use Illuminate\Support\Str;
use Slides\Connector\Auth\Sync\User as RemoteUser;

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
     * @param string $country
     *
     * @return array
     */
    public function register(int $userId, string $name, string $email, string $password, string $country)
    {
        if($this->disabled()) {
            return [];
        }

        return $this->client->request('register', compact('userId', 'name', 'email', 'password', 'country'));
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

        return Arr::get($response, 'user.email');
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
     * @param string|null $country Two-letter country code.
     *
     * @return array|false
     */
    public function update(int $id, ?string $name, ?string $email, ?string $password, ?string $country)
    {
        if($this->disabled()) {
            return false;
        }

        $attributes = array_filter(compact('id', 'name', 'email', 'password', 'country'));

        $response = $this->client->request('update', compact('id', 'attributes'));

        if(!$this->client->success(true)) {
            return false;
        }

        return $response;
    }

    /**
     * Safely delete a remote user.
     *
     * @param int $id Remote user ID.
     *
     * @return array|false
     */
    public function delete(int $id)
    {
        if($this->disabled()) {
            return false;
        }

        $response = $this->client->request('delete', compact('id'));

        if(!$this->client->success(true)) {
            return false;
        }

        return $response;
    }

    /**
     * Restore a remote user.
     *
     * @param int $id Remote user ID.
     *
     * @return array|false
     */
    public function restore(int $id)
    {
        if($this->disabled()) {
            return false;
        }

        $response = $this->client->request('restore', compact('id'));

        if(!$this->client->success(true)) {
            return false;
        }

        return $response;
    }

    /**
     * Retrieve a remote user
     *
     * @return RemoteUser|null
     */
    public function retrieveByToken()
    {
        if($this->disabled()) {
            return null;
        }

        try {
            $response = $this->client->request('me');
        }
        catch(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e) {
            return null;
        }

        if(!$this->client->success(true)) {
            return null;
        }

        return RemoteUser::createFromResponse($response);
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
        $handler = Str::camel(str_replace('.', ' ', $key));

        if(!method_exists($this->handlersContainer, $handler)) {
            throw new \InvalidArgumentException("Handler `{$handler}` cannot be found");
        }

        return $this->ensure(function() use ($handler, $parameters) {
            return call_user_func_array([$this->handlersContainer, $handler], $parameters);
        }, $fallback);
    }

    /**
     * Run a fallback handler.
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
        $key = 'fallback' . Str::studly($key);
        $parameters = array_merge(['guard' => $this->fallbackGuard], $parameters);

        return $this->handle($key, $parameters, $fallback);
    }

    /**
     * Store/retrieve a parameter from the cache.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return Cache|void
     */
    public function cache(string $key = null, $value = null)
    {
        $cache = new Cache();

        if(!func_num_args()) {
            return $cache;
        }

        if(func_num_args() === 1) {
            return $cache->get($key);
        }

        $cache->set($key, null, $value);
    }

    /**
     * Store/retrieve a user's parameter from the cache.
     *
     * @param int $remoteId The remote user ID.
     * @param string|null $key
     * @param mixed $value
     *
     * @return Cache|mixed|void
     */
    public function userCache(int $remoteId, string $key = null, $value = null)
    {
        $cache = new Cache();

        if(func_num_args() === 1) {
            return $cache->getUserParams($remoteId);
        }

        if(func_num_args() === 2) {
            return $cache->getUserParam($remoteId, $key);
        }

        $cache->setUserParam($remoteId, $key, $value);
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

    /**
     * Encrypt the given value.
     *
     * @param string $value
     *
     * @return string
     */
    public function encrypt(string $value): string
    {
        return $this->encrypter()->encrypt($value);
    }

    /**
     * Decrypt the given value.
     *
     * @param string $value
     *
     * @return string
     */
    public function decrypt(string $value): string
    {
        return $this->encrypter()->decrypt($value);
    }

    /**
     * Retrieve encrypter instance.
     *
     * @return Encrypter
     */
    protected function encrypter(): Encrypter
    {
        if (!$key = config('connector.credentials.auth.cryptKey')){
            throw new \RuntimeException('The crypt key should be provided.');
        }

        return new Encrypter(base64_decode($key), 'AES-256-CBC');
    }
}