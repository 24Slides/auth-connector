<?php

namespace Slides\Connector\Auth\Clients\Mandrill\Builders;

use Exception;
use Illuminate\Support\Collection;
use Slides\Connector\Auth\Clients\Mandrill\VariableResolver;

/**
 * Class Email
 *
 * @package Slides\Connector\Auth\Clients\Mandrill\Builders
 */
class Email
{
    /**
     * The variable resolver.
     *
     * @var VariableResolver
     */
    protected $resolver;

    /**
     * The additional attributes that should be added to email.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * @var string
     */
    protected $template;

    /**
     * @var array
     */
    protected $variables;

    /**
     * @var Collection
     */
    protected $users;

    /**
     * Email constructor.
     *
     * @param string $template
     * @param array $recipients
     * @param array $variables
     */
    public function __construct(string $template, array $recipients, array $variables)
    {
        $this->template = $template;
        $this->variables = $variables;

        $this->users = collect($recipients);

        $this->resolver = app(config('connector.credentials.clients.mandrill.resolver'));
    }

    /**
     * Retrieve users.
     *
     * @return Collection
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * Set the message subject.
     *
     * @param string $subject
     *
     * @return static
     */
    public function setSubject(string $subject)
    {
        $this->attributes['subject'] = $subject;

        return $this;
    }

    /**
     * Set the sender email address and name.
     *
     * @param string $email
     * @param string $name
     *
     * @return static
     */
    public function setFrom(string $email, ?string $name = null)
    {
        $this->attributes['from'] = [
            'email' => $email,
            'name' => $name
        ];

        return $this;
    }

    /**
     * Set the variable resolver.
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
     * Build the email to be sent.
     *
     * @param Collection|null $users
     *
     * @return array
     */
    public function build(Collection $users = null): array
    {
        if (is_null($users)) {
            $users = $this->users;
        }

        return array_merge([
            'template' => $this->template,
            'recipients' => $this->buildRecipients($users),
            'variables' => $this->buildVariables($users),
        ], $this->attributes);
    }

    /**
     * Chunk the recipients.
     *
     * @param int $size
     *
     * @return array
     */
    public function chunk(int $size = 1000): array
    {
        $chunks = $this->users->chunk($size);

        return $chunks->map([$this, 'build'])->toArray();
    }

    /**
     * Build the message recipients.
     *
     * @param Collection $users
     *
     * @return array
     */
    protected function buildRecipients(Collection $users): array
    {
        return $users->map(function (string $email) {
            return [
                'email' => $email,
                'type' => 'to'
            ];
        })->toArray();
    }

    /**
     * Build the variables for given collection of users.
     *
     * @param Collection $users
     *
     * @return array
     */
    protected function buildVariables(Collection $users): array
    {
        return $users->map(function (string $email) {
            return [
                'rcpt' => $email,
                'vars' => $this->userVariables($email)
            ];
        })->toArray();
    }

    /**
     * Build variable for a user.
     *
     * @param string $email
     *
     * @return array
     *
     * @throws Exception
     */
    protected function userVariables(string $email): array
    {
        return array_map(function ($variable) use ($email) {
            return $this->resolver->resolve($variable, $email);
        }, $this->variables);
    }
}