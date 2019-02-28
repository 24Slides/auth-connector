<?php

namespace Slides\Connector\Auth\Webhooks;

use Slides\Connector\Auth\Sync\Syncer;

/**
 * Class Webhook
 *
 * @package Slides\Connector\Auth\Webhooks
 */
abstract class Webhook
{
    /**
     * The request payload.
     *
     * @var array
     */
    protected $payload;

    /**
     * Validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * The payload validation rules.
     *
     * @return array
     */
    protected abstract function rules();

    /**
     * Handle the incoming request.
     *
     * @return array|null
     */
    public abstract function handle();

    /**
     * Webhook constructor.
     *
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->validator = \Illuminate\Support\Facades\Validator::make($this->payload, $this->rules());
    }

    /**
     * Validate a payload.
     *
     * @return bool
     */
    public function validate(): bool
    {
        return $this->validator->passes();
    }

    /**
     * Get validator instance.
     *
     * @return \Illuminate\Validation\Validator
     */
    public function getValidator(): \Illuminate\Validation\Validator
    {
        return $this->validator;
    }
}