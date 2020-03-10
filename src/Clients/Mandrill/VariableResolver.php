<?php

namespace Slides\Connector\Auth\Clients\Mandrill;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
     * VariableResolver constructor.
     *
     * @param Collection $emails
     */
    final public function __construct(Collection $emails)
    {
        $this->emails = $emails;

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
     *
     * @throws Exception
     */
    final public function resolve(string $variable, string $email): array
    {
        $name = Str::before($variable, ':');

        $arguments[] = $email;

        if (Str::contains($variable, ':')) {
            $arguments = array_merge($arguments, explode(',', Str::after($variable, $name . ':')));
        }

        $method = 'get' . ucfirst($name) . 'Variable';

        if (!method_exists($this, $method)) {
            throw new Exception('Method ' . $method . ' should be defined.');
        }

        return [
            'name' => $name,
            'content' => call_user_func([$this, $method], ...$arguments)
        ];
    }
}