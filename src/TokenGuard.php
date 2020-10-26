<?php

namespace Slides\Connector\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * Class TokenGuard
 *
 * @package App\Auth
 */
class TokenGuard implements \Illuminate\Contracts\Auth\Guard
{
    use GuardHelpers;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * The name of cookie parameter where bearer token stores.
     *
     * @var string
     */
    protected $authCookie;

    /**
     * The JWT token
     *
     * @var string
     */
    protected $token;

    /**
     * The last error message.
     *
     * @var string
     */
    protected $lastError;

    /**
     * TokenGuard constructor.
     *
     * @param UserProvider $provider
     * @param Request $request
     * @param AuthService $authService
     * @param Client|null $client
     */
    public function __construct(
        UserProvider $provider,
        Request $request,
        AuthService $authService,
        Client $client = null
    )
    {
        $this->provider = $provider;
        $this->request = $request;
        $this->authService = $authService;

        $this->client = $client ?? new Client();
        $this->authCookie = env('APP_AUTH_COOKIE', 'authKey');
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
        $response = $this->client->request('login', compact('email', 'password', 'remember'));

        if(!$this->client->success(true)) {
            $this->lastError = Arr::get($response, 'message');
            return false;
        }

        if(!$this->token = $this->client->getToken()) {
            return false;
        }

        $this->storeToken($this->token);

        return $this->token;
    }

    /**
     * Authenticate a user without the password.
     *
     * Warning! This method has implemented temporarily to make able to login users
     * who use Social Auth on 24Templates. MUST NOT be used in any other cases.
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     *
     * @return mixed
     *
     * @throws
     */
    public function unsafeLogin(string $email, bool $remember = false)
    {
        $this->client->request('unsafeLogin', compact('email', 'remember'));

        if(!$this->client->success()) {
            return false;
        }

        if(!$this->token = $this->client->getToken()) {
            return false;
        }

        $this->storeToken($this->token);

        return $this->token;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = null;

        if($token = $this->token()) {
            $user = $this->retrieveUserFromToken($token);
        }

        return $this->user = $user;
    }

    /**
     * Get the token for the current request.
     *
     * @return string|null
     */
    public function token()
    {
        if($this->token) {
            return $this->token;
        }

        $token = $this->request->cookie($this->authCookie);

        if (empty($token)) {
            $token = $this->request->bearerToken();
        }

        return $this->token = $token;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return true;
    }

    /**
     * Store a token
     *
     * @param string $token
     */
    private function storeToken(string $token)
    {
        $cookie = cookie()->forever($this->authCookie, $token, null, $this->domain());

        Cookie::queue($cookie);
    }

    /**
     * Retrieve a user model from the parsed JWT token.
     *
     * @param string $token
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|\Illuminate\Database\Query\Builder|null
     */
    private function retrieveUserFromToken(string $token)
    {
        try
        {
            $data = (array) \Firebase\JWT\JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        }
        catch(\RuntimeException $e) {
            $this->logout();

            return null;
        }

        if(!$userId = Arr::get($data, 'userId')) {
            return null;
        }

        return $this->provider->retrieveByCredentials(['remote_id' => $userId]);
    }

    /**
     * Invalidate a cookie
     *
     * @return void
     */
    public function logout()
    {
        Cookie::queue(Cookie::forget($this->authCookie, null, $this->domain()));
    }

    /**
     * Get last error message from the server.
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Retrieve current domain
     *
     * @return string
     */
    protected function domain(): string
    {
        $parts = explode('.', $host = request()->getHost());

        if (count($parts) < 2) {
            return $host;
        }

        return $parts[count($parts) - 2] . '.' . last($parts);
    }
}