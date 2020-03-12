<?php

namespace Slides\Connector\Auth\Clients\Mandrill;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Class VariableResolver
 *
 * @package Slides\Connector\Auth\Clients\Mandrill
 */
class VariableResolver
{
    /**
     * @var Collection
     */
    protected $emails;

    /**
     * @var array
     */
    protected $context;

    /**
     * VariableResolver constructor.
     *
     * @param Collection $emails
     * @param array $context
     */
    final public function __construct(Collection $emails, array $context)
    {
        $this->emails = $emails;
        $this->context = $context;

        if (method_exists($this, 'boot')) {
            app()->call([$this, 'boot']);
        }
    }

    /**
     * Resolver given variable.
     *
     * @param string $name
     * @param string $email
     *
     * @return array
     *
     * @throws Exception
     */
    final public function resolve(string $name, string $email): array
    {
        $method = 'get' . ucfirst($name) . 'Variable';

        if (!method_exists($this, $method)) {
            throw new Exception('Method ' . $method . ' should be defined.');
        }

        return [
            'name' => $name,
            'content' => call_user_func([$this, $method], $email)
        ];
    }

    /**
     * Get the variables from the context.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function get(string $key, $default = null)
    {
        return Arr::get($this->context, $key, $default);
    }
}