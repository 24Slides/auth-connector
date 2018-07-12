<?php

namespace Slides\Connector\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

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
     * The name of cookie parameter where bearer token stores.
     *
     * @var string
     */
    protected $authCookie = 'authKey';

    /**
     * The JWT token
     *
     * @var string
     */
    protected $token;

    /**
     * TokenGuard constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->client = new Client();
    }

    /**
     * Authenticate a user.
     *
     * @param string $username
     * @param string $password
     * @param bool $remember
     *
     * @return string|false
     */
    public function login(string $username, string $password, bool $remember = false)
    {
        $this->client->request('login', compact('username', 'password', 'remember'));

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
     * Create a remote user.
     *
     * @param int $userId
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    public function register(int $userId, string $username, string $password)
    {
        return $this->client->request('register', compact('userId', 'username', 'password'));
    }

    /**
     * Send an email with a password resetting link
     *
     * @param string $email
     *
     * @return bool
     */
    public function forgot(string $email)
    {
        $this->client->request('forgot', compact('email'));

        return $this->client->success(true);
    }

    /**
     * Checks whether password reset token is valid
     *
     * @param string $email
     * @param string $token
     *
     * @return string|false
     */
    public function validatePasswordResetToken(string $email, string $token)
    {
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
     */
    public function resetPassword(string $token, string $email, string $password, string $confirmation)
    {
        $response = $this->client->request('reset', compact('token', 'email', 'password', 'confirmation'));

        if(!$this->client->success(true)) {
            return false;
        }

        return $response;
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
     * @return string
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

        return $token;
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
        $cookie = cookie($this->authCookie, $token);

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

        if(!$userId = array_get($data, 'userId')) {
            return null;
        }

        return $this->provider->retrieveById($userId);
    }

    /**
     * Invalidate a cookie
     *
     * @return void
     */
    public function logout()
    {
        Cookie::queue(Cookie::forget($this->authCookie));
    }
}