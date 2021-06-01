<?php

namespace Slides\Connector\Auth\Clients\Mandrill;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Slides\Connector\Auth\Clients\Mandrill\Contracts\VariableResolver;

/**
 * Class Resolver
 *
 * @package Slides\Connector\Auth\Clients\Mandrill
 */
abstract class Resolver implements VariableResolver
{
    /**
     * @var Collection
     */
    protected Collection $emails;

    /**
     * @var array
     */
    protected array $context;

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
     * @param string $variable
     * @param string $email
     *
     * @return array
     */
    public function resolve(string $variable, string $email): array
    {
        $method = 'get' . Str::studly($variable);

        throw_unless(method_exists($this, $method), new Exception('Method ' . $method . ' should be defined.'));

        return [
            'name' => $variable,
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