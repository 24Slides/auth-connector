<?php

namespace Slides\Connector\Auth\Tests\Feature\Webhooks;

use Slides\Connector\Auth\Webhooks\UserSyncWebhook;

class UserSyncWebhookTest extends \Slides\Connector\Auth\Tests\TestCase
{
    public function testValidate()
    {
        $webhook = new UserSyncWebhook($this->payload());
        
        static::assertTrue($webhook->validate());
    }

    /**
     * The request payload.
     *
     * @return array
     */
    private function payload(): array
    {
        return [
            'user' => [
                'id' => 1,
                'name' => 'Test',
                'email' => 'webhook@test.com',
                'country' => 'US',
                'password' => 'qwerty',
                'created_at' => '2018-08-01 00:00:00',
                'updated_at' => '2018-08-01 00:00:00',
                'action' => 'create'
            ]
        ];
    }
}