<?php

namespace Slides\Connector\Auth\Tests\Unit;

use Slides\Connector\Auth\Client;

class ClientTest extends \Slides\Connector\Auth\Tests\TestCase
{
    /**
     * @covers \Slides\Connector\Auth\Client::boot()
     * @covers \Slides\Connector\Auth\Client::signature()
     * @covers \Slides\Connector\Auth\Client::credential()
     * @covers \Slides\Connector\Auth\Client::bearerTokenHeader()
     */
    public function testBoot()
    {
        $client = new Client();
        $httpClient = $client->getClient();

        static::assertSame($httpClient->getConfig('headers'), [
            'X-Tenant-Key' => 'dummy',
            'X-Tenant-Sign' => '1a71f4efd61c5759ce2fde1ac0cdb830128270ee8355727ba698c2487c588a47',
            'User-Agent' => null
        ]);

        static::assertFalse($httpClient->getConfig('http_errors'));
    }

    public function testHasRequest()
    {
        $client = new Client();

        static::assertTrue($client->hasRequest('login'));
        static::assertFalse($client->hasRequest('unknown'));
    }

    public function testInvalidRequest()
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = new Client();
        $client->request('unknown');
    }
}