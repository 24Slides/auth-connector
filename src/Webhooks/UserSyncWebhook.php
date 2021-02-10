<?php

namespace Slides\Connector\Auth\Webhooks;

use Illuminate\Support\Arr;
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
            'user.country' => 'nullable|string|size:2',
            'user.password' => 'string',
            'user.created_at' => 'required|string',
            'user.updated_at' => 'string',
            'user.action' => 'required|string',
        ];
    }
    
    /**
     * Handle the incoming request.
     *
     * @param array $payload
     *
     * @return void
     */
    public function handle(array $payload)
    {
        $user = Arr::get($payload, 'user');

        $syncer = new Syncer(null, [Syncer::MODE_PASSWORDS]);
        $syncer->setForeigners(collect([
            $syncer->createRemoteUserFromResponse($user)
        ]));

        $syncer->apply();
    }
}