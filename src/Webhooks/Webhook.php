<?php

namespace Slides\Connector\Auth\Webhooks;

use Slides\Connector\Auth\Sync\Syncer;

/**
 * Class Webhook
 *
 * @package Slides\Connector\Auth\Webhooks
 */
class Webhook
{
    /**
     * The request payload.
     *
     * @var array
     */
    protected $payload;

    /**
     * Webhook constructor.
     *
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}