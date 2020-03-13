<?php

namespace Slides\Connector\Auth\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * Class AbstractClient
 *
 * @package Slides\Connector\Auth\Clients
 */
abstract class AbstractClient
{
    /**
     * HTTP client
     *
     * @var Client
     */
    protected $client;

    /**
     * Base URL of resource
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Client's request
     *
     * @var Request
     */
    protected $request;

    /**
     * Client's response
     *
     * @var Response
     */
    protected $response;

    /**
     * HTTP request headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The query parameters.
     *
     * @var array
     */
    protected $query = [];

    /**
     * The body of an entity enclosing request.
     *
     * @var array
     */
    protected $body;

    /**
     * The composed credentials.
     *
     * @var array
     */
    protected $credentials = [];

    /**
     * Whether client is aborted to perform requests
     *
     * @var bool
     */
    protected $isAborted = false;

    /**
     * List of supported requests
     *
     * @return array
     */
    abstract public function requests(): array;

    /**
     * Authorize the client
     *
     * @return void
     */
    abstract protected function authorize();

    /**
     * Get client's credentials
     *
     * @return array
     */
    abstract protected function credentials(): array;

    /**
     * BaseClient constructor.
     *
     * @param array $config
     * @param array $credentials
     */
    public function __construct(array $config = [], array $credentials = [])
    {
        $this->credentials = array_merge($this->credentials(), $credentials);

        $this->client = new Client(array_merge([
            'base_uri' => $this->baseUrl,
            'handler' => $this->createClientHandler(),
            'http_errors' => false,
            'headers' => [
                'User-Agent' => null
            ]
        ], $config));

        $this->boot();
    }

    /**
     * Boot the client
     */
    protected function boot()
    {
        if (!$this->baseUrl) {
            throw new InvalidArgumentException('Base URL should be defined');
        }

        $this->authorize();
    }

    /**
     * Send a request
     *
     * @param AbstractRequest $request
     * @param array $options Request options
     *
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function request(AbstractRequest $request, array $options = [])
    {
        if (!$this->resolveRequest($request)) {
            throw new InvalidArgumentException(static::class . ': ' . get_class($request) . ' should be registered');
        }

        $request->mergeOptions($options)
            ->mergeOptions(array_filter([
                'query' => $this->query,
                'json' => $this->body
            ]))
            ->compose();

        $response = $this->send(
            $request->getMethod(),
            $request->getUri(),
            $request->getOptions()
        );

        $request->setResponse($response);

        // Validate a response
        if (!$request->success()) {
            throw new RuntimeException(json_encode($response, JSON_PRETTY_PRINT));
        }

        return $response;
    }

    /**
     * Send a request
     *
     * @param string $method
     * @param string $url
     * @param array $options
     *
     * @return array|null|string
     *
     * @throws
     */
    public function send(string $method, string $url, array $options = [])
    {
        if ($this->isAborted) {
            throw new RuntimeException('Cannot send a request while client is aborted');
        }

        $this->request = new Request($method, $url, $this->headers);

        if (!$this->response = $this->client->send($this->request, $options)) {
            return null;
        }

        $contents = (string) $this->response->getBody();

        return json_decode($contents, true) ?: $contents;
    }

    /**
     * Set headers
     *
     * @param array $headers
     */
    public function headers(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Add query parameters.
     *
     * @param array $parameters
     *
     * @return void
     */
    public function query(array $parameters)
    {
        $this->query = array_merge($this->query, $parameters);
    }

    /**
     * Add body parameters.
     *
     * @param array $parameters
     */
    public function body(array $parameters)
    {
        $this->body = array_merge((array) $this->body, $parameters);
    }

    /**
     * Get credentials parameter
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function credential(string $key, $default = null)
    {
        return Arr::get($this->credentials, $key, $default);
    }

    /**
     * Set the client as aborted and send notification
     *
     * @return void
     */
    public function abort()
    {
        $this->isAborted = true;
    }

    /**
     * Get response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Create a handler for Guzzle HTTP Client
     *
     * @return HandlerStack
     */
    private function createClientHandler()
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $this->request = $request;
        }));

        return $stack;
    }

    /**
     * Resolve request
     *
     * @param AbstractRequest $request
     *
     * @return AbstractRequest|null
     */
    private function resolveRequest(AbstractRequest $request)
    {
        foreach ($this->requests() as $class) {
            if (class_basename($class) !== class_basename($request)) {
                continue;
            }

            return $request;
        }

        return null;
    }
}