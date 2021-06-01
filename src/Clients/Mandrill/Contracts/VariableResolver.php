<?php

namespace Slides\Connector\Auth\Clients\Mandrill\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface VariableResolver
 *
 * @package Slides\Connector\Auth\Clients\Mandrill\Contracts
 */
interface VariableResolver
{
    /**
     * VariableResolver constructor.
     *
     * @param Collection $emails
     * @param array $context
     */
    public function __construct(Collection $emails, array $context);

    /**
     * Resolver given variable.
     *
     * @param string $variable
     * @param string $email
     *
     * @return array
     */
    public function resolve(string $variable, string $email): array;
}