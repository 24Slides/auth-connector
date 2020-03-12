<?php

namespace Slides\Connector\Auth\Clients;

/**
 * Class AbstractRequest
 *
 * @package Slides\Connector\Auth\Clients
 */
abstract class AbstractRequest
{
    /**
     * HTTP method
     *
     * @var string
     */
    protected $method;

    /**
     * Request URI
     *
     * @var string
     */
    protected $uri;

    /**
     * Response of the request
     *
     * @var mixed
     */
    protected $response;

    /**
     * @var AbstractClient
     */
    protected $client;

    /**
     * Request options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Request attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Compose a request
     *
     * @return void
     */
    abstract public function compose();

    /**
     * Validate a response
     *
     * @return bool
     */
    abstract public function success(): bool;

    /**
     * AbstractRequest constructor.
     *
     * @param AbstractClient $client
     * @param array $attributes
     * @param array $options
     */
    public function __construct(AbstractClient $client, array $attributes = [], array $options = [])
    {
        $this->client = $client;
        $this->setAttributes($attributes);
        $this->options = $options;
    }

    /**
     * Get request method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request URI
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get request options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set a response
     *
     * @param mixed $response
     *
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Set attributes
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Retrieve an attribute value
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function attribute(string $key, $default = null)
    {
        return array_get($this->attributes, $key, $default);
    }

    /**
     * Set an attribute value
     *
     * @param string $key
     * @param $value
     */
    public function setAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Set body params (as JSON)
     *
     * @param array $params
     *
     * @return $this
     */
    public function body(array $params)
    {
        $this->mergeOptions(['json' => $params]);

        return $this;
    }

    /**
     * Set query params
     *
     * @param array $params
     *
     * @return $this
     */
    public function query(array $params)
    {
        $this->mergeOptions(['query' => $params]);

        return $this;
    }

    /**
     * Merge request options.
     *
     * @param array $options
     *
     * @return $this
     */
    public function mergeOptions(array $options)
    {
        $this->options = array_merge_recursive($this->options, $options);

        return $this;
    }

    /**
     * Get attribute value
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->attribute($name);
    }
}