<?php

namespace Slides\Connector\Auth\Webhooks;

use Slides\Connector\Auth\Sync\Syncer;

/**
 * Class UserSyncWebhook
 *
 * @package Slides\Connector\Auth\Webhooks
 */
class UserSyncWebhook extends Webhook
{
    /**
     * The payload validation rules.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'user' => 'required|array',
            'user.id' => 'required|int',
            'user.name' => 'string',
            'user.email' => 'required|email',
            'user.country' => 'required|string|size:2',
            'user.password' => 'string',
            'user.created_at' => 'required|string',
            'user.updated_at' => 'string',
            'user.action' => 'required|string',
        ];
    }
    
    /**
     * Handle the incoming request.
     *
     * @return void
     */
    public function handle()
    {
        $user = array_get($this->payload, 'user');

        $syncer = new Syncer(null, [Syncer::MODE_PASSWORDS]);
        $syncer->setForeigners(collect([
            $syncer->createRemoteUserFromResponse($user)
        ]));

        $syncer->apply();
    }
}