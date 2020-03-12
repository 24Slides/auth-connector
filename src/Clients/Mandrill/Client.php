<?php

namespace Slides\Connector\Auth\Clients\Mandrill;

use Slides\Connector\Auth\Clients\AbstractClient;
use Slides\Connector\Auth\Clients\Mandrill\Requests;

/**
 * Class Client
 *
 * @package Slides\Connector\Auth\Clients\Mandrill
 */
class Client extends AbstractClient
{
    /**
     * Base URL of resource
     *
     * @var string
     */
    protected $baseUrl = 'https://mandrillapp.com/api/1.0/';

    /**
     * Get client's credentials
     *
     * @return array
     */
    protected function credentials(): array
    {
        return config('connector.credentials.clients.mandrill', []);
    }

    /**
     * Authorize the client
     *
     * @return void
     */
    protected function authorize()
    {
        if (!$secret = $this->credential('secretKey')) {
            $this->abort();

            return;
        }

        $this->body([
            'key' => $secret
        ]);
    }

    /**
     * List of supported requests
     *
     * @return array
     */
    public function requests(): array
    {
        return [
            Requests\SendTemplate::class
        ];
    }

    /**
     * Send a new transactional message through Mandrill using a template
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function sendTemplate(array $attributes)
    {
        return $this->request(new Requests\SendTemplate($this, $attributes));
    }
}