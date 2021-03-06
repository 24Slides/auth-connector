<?php

namespace Slides\Connector\Auth;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Slides\Connector\Auth\Exceptions\ValidationException;
use Slides\Connector\Auth\Concerns\WritesLogs;

/**
 * Class Client
 *
 * @package App\Services\Auth
 */
class Client
{
    use WritesLogs;

    /**
     * The HTTP client
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Instance of the last response
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * The formatted last response
     *
     * @var array
     */
    protected $formatted = [];

    /**
     * The list of supported requests
     *
     * @var array
     */
    protected $requests = [
        'login', 'unsafeLogin', 'register', 'refresh', 'me', 'update',
        'forgot', 'validateReset', 'reset', 'sync', 'delete', 'restore'
    ];

    /**
     * Client constructor.
     *
     * @param HttpClient|null $client
     */
    public function __construct(HttpClient $client = null)
    {
        $this->client = $client;

        $this->boot();
    }

    /**
     * Initialize the client.
     */
    protected function boot()
    {
        if(!$publicKey = $this->credential('public')) {
            throw new \InvalidArgumentException('Public key must be defined');
        }

        if(!$secretKey = $this->credential('secret')) {
            throw new \InvalidArgumentException('Secret key must be defined');
        }

        if(!$this->client) {
            $handler = new \GuzzleHttp\HandlerStack();
            $handler->setHandler(new \GuzzleHttp\Handler\CurlHandler());
            $handler->push($this->bearerTokenHeader());

            $this->client = new HttpClient([
                'handler' => $handler,
                'base_uri' => Str::finish($this->credential('url'), '/'),
                'headers' => [
                    'X-Tenant-Key' => $publicKey,
                    'X-Tenant-Sign' => $this->signature($publicKey, $secretKey),
                    'User-Agent' => null
                ],
                'http_errors' => false
            ]);
        }
    }

    /**
     * Login a user
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     *
     * @return ResponseInterface
     */
    protected function login(string $email, string $password, bool $remember = false): ResponseInterface
    {
        return $this->client->post('login', ['json' => [
            'email' => $email,
            'password' => $password,
            'remember' => $remember
        ]]);
    }

    /**
     * Login a user
     *
     * Warning! This method has implemented temporarily to make able to login users
     * who use Social Auth on 24Templates. MUST NOT be used in any other cases.
     *
     * @param string $email
     * @param bool $remember
     *
     * @return ResponseInterface
     */
    protected function unsafeLogin(string $email, bool $remember = false): ResponseInterface
    {
        return $this->client->post('unsafe-login', ['json' => [
            'email' => $email,
            'remember' => $remember
        ]]);
    }

    /**
     * Register a user
     *
     * @param int $userId Local user ID
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string|null $country
     *
     * @return ResponseInterface
     */
    protected function register(int $userId, string $name, string $email, string $password, ?string $country): ResponseInterface
    {
        return $this->client->post('register', ['json' => [
            'userId' => $userId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'country' => $country
        ]]);
    }

    /**
     * Send an email with a password resetting link
     *
     * @param string $email
     *
     * @return ResponseInterface
     */
    protected function forgot(string $email): ResponseInterface
    {
        return $this->client->post('forgot', ['json' => [
            'email' => $email
        ]]);
    }

    /**
     * Send an email with a password resetting link
     *
     * @param string $token
     * @param string $email
     *
     * @return ResponseInterface
     */
    protected function validateReset(string $token, string $email): ResponseInterface
    {
        return $this->client->post('reset/' . $token . '/' . $email . '/validate', ['json' => [
            'email' => $email
        ]]);
    }

    /**
     * Reset user's password
     *
     * @param string $token
     * @param string $email
     * @param string $password
     * @param string $confirmation
     *
     * @return ResponseInterface
     */
    protected function reset(string $token, string $email, string $password, string $confirmation): ResponseInterface
    {
        return $this->client->post('reset/' . $token . '/' . $email, ['json' => [
            'password' => $password,
            'password_confirmation' => $confirmation
        ]]);
    }

    /**
     * Synchronize remote and local users
     *
     * @param array $users
     * @param array $modes
     *
     * @return ResponseInterface
     */
    protected function sync(array $users, array $modes): ResponseInterface
    {
        return $this->client->post('sync', [
            'json' => [
                'users' => $users,
                'modes' => $modes
            ],
            'timeout' => 0
        ]);
    }

    /**
     * Update a remote user
     *
     * @param int $id Local user ID
     * @param array $attributes
     *
     * @return ResponseInterface
     */
    protected function update(int $id, array $attributes): ResponseInterface
    {
        return $this->client->post('update', ['json' => array_merge(
            ['userId' => $id], $attributes
        )]);
    }

    /**
     * Retrieve an authenticated user
     *
     * @return ResponseInterface
     */
    protected function me(): ResponseInterface
    {
        return $this->client->get('me');
    }

    /**
     * Delete a remote user.
     *
     * @param int $id Local user ID.
     *
     * @return ResponseInterface
     */
    protected function delete(int $id): ResponseInterface
    {
        return $this->client->post('delete/' . $id);
    }

    /**
     * Restore a remote user.
     *
     * @param int $id Local user ID.
     *
     * @return ResponseInterface
     */
    protected function restore(int $id): ResponseInterface
    {
        return $this->client->post('restore/' . $id);
    }

    /**
     * Make a request and retrieve a formatted response.
     *
     * @param string $name
     * @param array $parameters
     *
     * @return array
     *
     * @throws
     */
    public function request(string $name, array $parameters = []): array
    {
        if(!$this->hasRequest($name)) {
            throw new \InvalidArgumentException("Request `{$name}` is not supported");
        }

        if(!method_exists($this, $name)) {
            throw new \InvalidArgumentException("Request `{$name}` listed but is not implemented");
        }

        $this->log("Sending a {$name} request", $parameters);

        return $this->parseResponse(
            $this->response = call_user_func_array([$this, $name], $parameters)
        );
    }

    /**
     * Checks whether a request is supported
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasRequest(string $name): bool
    {
        return in_array($name, $this->requests);
    }

    /**
     * Checks whether status of the response is successful
     *
     * @param bool $withStatus Whether need to check for "success" status from the response
     *
     * @return bool
     */
    public function success(bool $withStatus = false): bool
    {
        if(!$this->response || $this->response->getStatusCode() !== 200) {
            return false;
        }

        return $withStatus
            ? Arr::get($this->formatted, 'status') === 'success'
            : true;
    }

    /**
     * Retrieve a JWT token from the response
     *
     * @return string|null
     */
    public function getToken()
    {
        return Arr::get($this->formatted, 'token');
    }

    /**
     * @param HttpClient $client
     */
    public function setClient(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return HttpClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Make a signature based on public and secret keys
     *
     * @param string $public
     * @param string $secret
     *
     * @return string
     */
    private function signature(string $public, string $secret)
    {
        return hash('sha256', $public . $secret);
    }

    /**
     * Parse a response
     *
     * @param ResponseInterface $response
     *
     * @return array
     *
     * @throws \Slides\Connector\Auth\Exceptions\HttpException
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $this->log('Got a response. Status: ' . $response->getStatusCode());

        $decoded = (string) $response->getBody();
        $decoded = json_decode($decoded, true);

        $this->formatted = $decoded;

        $this->log(null, $decoded ?? []);

        if($this->success()) {
           return $this->formatted;
        }

        $message = Arr::get($this->formatted, 'message');

        switch ($this->response->getStatusCode()) {
            case \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY: {
                throw ValidationException::create($message);
                break;
            }
            case \Illuminate\Http\Response::HTTP_UNAUTHORIZED: {
                throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('auth', $message);
                break;
            }
            default: {
                throw new \Slides\Connector\Auth\Exceptions\HttpException($message);
            }
        }
    }

    /**
     * Retrieve authorization Bearer token.
     *
     * @return string|null
     */
    private function bearerToken()
    {
        if(!$token = app('auth')->token()) {
            return null;
        }

        return 'Bearer ' . $token;
    }

    /**
     * A Guzzle middleware of injecting the Bearer authentication token
     *
     * @return \Closure
     */
    public function bearerTokenHeader(): \Closure
    {
        return function(callable $handler) {
            return function (\Psr\Http\Message\RequestInterface $request, array $options) use ($handler) {
                $request = $request->withHeader('Authorization', $this->bearerToken());

                return $handler($request, $options);
            };
        };
    }

    /**
     * Retrieve a credential value
     *
     * @param string $key
     * @param mixed $default
     *
     * @return string|null
     */
    private function credential(string $key, $default = null)
    {
        return Arr::get(config('connector.credentials.auth', []), $key, $default);
    }
}