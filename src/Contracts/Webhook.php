<?php

namespace Slides\Connector\Auth\Contracts;

/**
 * Interface Webhook
 * 
 * @package Slides\Connector\Auth\Contracts
 */
interface Webhook
{
    /**
     * Handle the incoming request.
     *
     * @param array $payload
     *
     * @return array
     */
    public function handle(array $payload);
}