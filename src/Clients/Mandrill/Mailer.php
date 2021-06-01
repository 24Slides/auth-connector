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
    protected Client $client;

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
        return new static(new Client([], ['secretKey' => $secret]));
    }

    /**
     * Begin the process of mailing a mail class instance.
     *
     * @param string $template
     *
     * @return \Slides\Connector\Auth\Clients\Mandrill\Builders\Email
     */
    public function template(string $template)
    {
        return $this->forward(__FUNCTION__, $template);
    }

    /**
     * Begin the process of mailing a mail class instance.
     *
     * @param array|string $variables
     *
     * @return \Slides\Connector\Auth\Clients\Mandrill\Builders\Email
     */
    public function variables(...$variables)
    {
        return $this->forward(__FUNCTION__, ...$variables);
    }

    /**
     * Begin the process of mailing a mail class instance.
     *
     * @param string $subject
     *
     * @return \Slides\Connector\Auth\Clients\Mandrill\Builders\Email
     */
    public function subject(string $subject)
    {
        return $this->forward(__FUNCTION__, $subject);
    }

    /**
     * Set a message tags.
     *
     * @param array|string $tags
     *
     * @return \Slides\Connector\Auth\Clients\Mandrill\Builders\Email
     */
    public function tags(...$tags)
    {
        return $this->forward(__FUNCTION__, ...$tags);
    }

    /**
     * Begin the process of mailing a mail class instance.
     *
     * @param string $email
     * @param string|null $name
     *
     * @return \Slides\Connector\Auth\Clients\Mandrill\Builders\Email
     */
    public function from(string $email, ?string $name = null)
    {
        return $this->forward(__FUNCTION__, $email, $name);
    }

    /**
     * Begin the process of mailing a mail class instance.
     *
     * @param array|string $recipients
     *
     * @return \Slides\Connector\Auth\Clients\Mandrill\Builders\Email
     */
    public function recipients(...$recipients)
    {
        return $this->forward(__FUNCTION__, ...$recipients);
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
     * Forward call to email builder instance.
     *
     * @param string $method
     * @param mixed ...$arguments
     *
     * @return Email
     */
    protected function forward(string $method, ...$arguments)
    {
        if (!method_exists($email = new Email($this), $method)) {
            throw new \BadMethodCallException('Method ' . $method . ' is not defined.');
        }

        return call_user_func([$email, $method], ...$arguments);
    }
}