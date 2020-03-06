<?php

namespace Slides\Connector\Auth\Clients\Mandrill;

use Exception;

/**
 * Class VariableResolver
 *
 * @package Slides\Connector\Auth\Clients\Mandrill
 */
class VariableResolver
{
    /**
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
}