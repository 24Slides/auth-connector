<?php

namespace Slides\Connector\Auth\Webhooks;

use Slides\Connector\Auth\Exceptions\WebhookException;

/**
 * Class Dispatcher
 *
 * @package Slides\Connector\Auth\Webhooks
 */
class Dispatcher
{
    /**
     * Handle a webhook.
     *
     * @param string $key
     * @param array $payload
     *
     * @return void
     */
    public function handle(string $key, array $payload)
    {
        if(!$this->has($key)) {
            throw new WebhookException("Webhook with key \"{$key}\" cannot be found.");
        }

        $webhook = $this->instantiate($key, $payload);
        $webhook->handle();
    }

    /**
     * Instantiate a webhook handler.
     *
     * @param string $key
     * @param array $payload
     *
     * @return mixed
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