<?php

namespace Slides\Connector\Auth\Webhooks;

use Illuminate\Support\Arr;
use Slides\Connector\Auth\Exceptions\WebhookException;
use Slides\Connector\Auth\Exceptions\WebhookValidationException;
use Slides\Connector\Auth\Concerns\WritesLogs;
use Slides\Connector\Auth\Contracts\Webhook as WebhookContract;

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

        $webhook = $this->instantiate($key);

        if(!$webhook->validate($payload)) {
            throw new WebhookValidationException($webhook->getValidator());
        }

        try {
            return $webhook->handle($payload);
        }
        catch(\Exception $e) {
            throw new WebhookException(get_class($webhook) . ': ' . $e->getMessage());
        }
    }

    /**
     * Instantiate a webhook handler.
     *
     * @param string $key
     *
     * @return Webhook|WebhookContract
     */
    private function instantiate(string $key)
    {
        $webhook = $this->get($key);

        return app($webhook);
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
        return Arr::get(static::webhooks(), $key, $default);
    }

    /**
     * The supported webhook identifiers.
     *
     * @return array
     */
    public static function webhooks()
    {
        return [
            'user.sync' => UserSyncWebhook::class,
            'assess.users' => AssessUsersWebhook::class
        ];
    }
}