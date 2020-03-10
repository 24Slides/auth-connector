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
class Mailer
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var VariableResolver
     */
    protected $resolver;

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
    public function setToken(string $secret)
    {
        return new static(new Client([], [
            'secretKey' => $secret
        ]));
    }

    /**
     * Set variable resolver instance.
     *
     * @param VariableResolver $resolver
     *
     * @return static
     */
    public function setResolver(VariableResolver $resolver)
    {
        $this->resolver = $resolver;

        return $this;
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
     * @param string $name
     * @param array $arguments
     *
     * @return Email
     */
    public function __call(string $name , array $arguments)
    {
        $email = new Email($this, $this->resolver);

        if (!method_exists($email, $name)) {
            throw new \BadMethodCallException('Method ' . $name . ' is not defined.');
        }

        return call_user_func([$email, $name], ...$arguments);
    }
}