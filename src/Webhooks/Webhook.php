<?php

namespace Slides\Connector\Auth\Webhooks;

use Illuminate\Support\Facades\Validator;

/**
 * Class Webhook
 *
 * @package Slides\Connector\Auth\Webhooks
 */
abstract class Webhook implements \Slides\Connector\Auth\Contracts\Webhook
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
     * Validate a payload.
     *
     * @param array $payload
     *
     * @return bool
     */
    public function validate(array $payload): bool
    {
        $this->validator = Validator::make($payload, $this->rules());

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