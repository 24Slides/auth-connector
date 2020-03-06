<?php

namespace Slides\Connector\Auth\Clients\Mandrill;

use Slides\Connector\Auth\Clients\Mandrill\Builders\Email;

/**
 * Class Email
 *
 * @mixin Email
 *
 * @package Slides\Connector\Auth\Clients\Mandrill
 */
class Emailer
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Email constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set the Mandrill API token.
     *
     * @param string $secret
     *
     * @return static
     */
    public function token(string $secret)
    {
        return new static(new Client([], [
            'secretKey' => $secret
        ]));
    }

    /**
     * Send the email.
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function send(array $attributes)
    {
        return $this->client->sendTemplate($attributes);
    }

    /**
     * Forward calls to Email builder.
     *
     * @param $name
     * @param $value
     *
     * @return Email
     */
    public function __set($name, $value)
    {
        $email = new Email($this);

        if (!method_exists($email, $name)) {
            throw new \BadMethodCallException('Method ' . $name . ' is not defined.');
        }

        return call_user_func([$email, $name], $value);
    }
}