<?php

namespace Slides\Connector\Auth\Webhooks;

use Slides\Connector\Auth\Exceptions\WebhookException;
use Slides\Connector\Auth\Exceptions\WebhookValidationException;
use Slides\Connector\Auth\Concerns\WritesLogs;

/**
 * Class Dispatcher
 *
 * @package Slides\Connector\Auth\Webhooks
 */
class Dispatcher
{
    use WritesLogs;

    /**
     * Handle a webhook.
     *
     * @param string $key
     * @param array $payload
     *
     * @return array|null
     *
     * @throws WebhookException
     * @throws WebhookValidationException
     */
    public function handle(string $key, array $payload)
    {
        if(!$this->has($key)) {
            throw new WebhookException("Webhook with key \"{$key}\" cannot be found.");
        }

        $webhook = $this->instantiate($key, $payload);

        if(!$webhook->validate()) {
            throw new WebhookValidationException($webhook->getValidator());
        }

        try {
            return $webhook->handle();
        }
        catch(\Exception $e) {
            throw new WebhookException(get_class($webhook) . ': ' . $e->getMessage());
        }
    }

    /**
     * Instantiate a webhook handler.
     *
     * @param string $key
     * @param array $payload
     *
     * @return Webhook
     */
    private function instantiate(string $key, array $payload)
    {
        $webhook = $this->get($key);

        return new $webhook($payload);
    }

    /**
     * Check whether a given webhook exists.
     *
     * @param string $key
     *
     * @return bool
     */
    private function has(string $key): bool
    {
        return array_key_exists($key, static::webhooks());
    }

    /**
     * Retrieve a class handler of by the webhook key.
     *
     * @param string $key
     * @param string|null $default
     *
     * @return string|null
     */
    private function get(string $key, $default = null)
    {
        return array_get(static::webhooks(), $key, $default);
    }

    /**
     * The supported webhook identifiers.
     *
     * @return array
     */
    public static function webhooks()
    {
        return [
            'user.sync' => UserSyncWebhook::class
        ];
    }
}