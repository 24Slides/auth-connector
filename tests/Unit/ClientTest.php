<?php

namespace Slides\Connector\Auth\Tests\Unit;

use Slides\Connector\Auth\Client;

class ClientTest extends \Slides\Connector\Auth\Tests\TestCase
{
    /**
     * @covers Client::boot()
     * @covers Client::signature()
     * @covers Client::credential()
     * @covers Client::bearerTokenHeader()
     */
    public function testBoot()
    {
        $client = new Client();
        $httpClient = $client->getClient();

        static::assertArraySubset([
            'headers' => [
                'X-Tenant-Key' => 'dummy',
                'X-Tenant-Sign' => '1a71f4efd61c5759ce2fde1ac0cdb830128270ee8355727ba698c2487c588a47'
            ],
            'http_errors' => false
        ], $httpClient->getConfig());
    }

    public function testHasRequest()
    {
        $client = new Client();

        static::assertTrue($client->hasRequest('login'));
        static::assertFalse($client->hasRequest('unknown'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidRequest()
    {
        $client = new Client();
        $client->request('unknown');
    }
}