<?php

namespace Slides\Connector\Auth\Webhooks;

use Slides\Connector\Auth\Sync\Syncer;
use Slides\Connector\Auth\Exceptions\WebhookValidationException;

/**
 * Class UserSyncWebhook
 *
 * @package Slides\Connector\Auth\Webhooks
 */
class UserSyncWebhook extends Webhook
{
    /**
     * Handle the incoming request.
     *
     * @return void
     *
     * @throws WebhookValidationException
     */
    public function handle()
    {
        $user = array_get($this->payload, 'user');

        if(!is_array($user)) {
            throw new WebhookValidationException('payload.user is required');
        }

        $syncer = new Syncer(null, [Syncer::MODE_PASSWORDS]);
        $syncer->setForeigners(collect([
            $syncer->createRemoteUserFromResponse($user)
        ]));

        $syncer->apply();
    }
}