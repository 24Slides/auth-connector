<?php

namespace Slides\Connector\Auth\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Trait WritesLogs
 *
 * @package Slides\Connector\Auth\Concerns
 */
trait WritesLogs
{
    /**
     * The list of parameters that must be hidden.
     *
     * @var array
     */
    protected $excludeLoggingParameters = [
        'password', 'password_confirmation', 'passwordConfirmation',
        'password_confirm', 'passwordConfirm', 'confirmation'
    ];

    /**
     * Send a message to logger.
     *
     * @param string|null $message
     * @param array $context
     *
     * @return void
     */
    protected function log(?string $message, array $context = [])
    {
        $securedContext = \Slides\Connector\Auth\Helpers\ArrayHelper::replaceValuesByMatchingKeys(
            $context, $this->excludeLoggingParameters, '...'
        );

        Log::debug('[Connector] ' . $message, $securedContext);
    }
}