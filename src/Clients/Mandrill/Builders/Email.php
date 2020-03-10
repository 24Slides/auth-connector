<?php

namespace Slides\Connector\Auth\Clients\Mandrill\Builders;

use Exception;
use Illuminate\Support\Collection;
use Slides\Connector\Auth\Clients\Mandrill\Mailer;
use Slides\Connector\Auth\Clients\Mandrill\VariableResolver;

/**
 * Class Email
 *
 * @package Slides\Connector\Auth\Clients\Mandrill\Builders
 */
class Email
{
    /**
     * @var Mailer
     */
    protected $mailer;

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
    protected $recipients;

    /**
     * Email constructor.
     *
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;

        $this->resolver = app(config('connector.credentials.clients.mandrill.resolver'));
    }

    /**
     * Retrieve recipients.
     *
     * @return Collection
     */
    public function getRecipients(): Collection
    {
        return $this->recipients;
    }

    /**
     * Set the email template.
     *
     * @param string $template
     *
     * @return $this
     */
    public function template(string $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Set additional variables.
     *
     * @param array $variables
     *
     * @return $this
     */
    public function variables(array $variables)
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * Set the message subject.
     *
     * @param string $subject
     *
     * @return static
     */
    public function subject(string $subject)
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
    public function from(string $email, ?string $name = null)
    {
        $this->attributes['from'] = [
            'email' => $email,
            'name' => $name
        ];

        return $this;
    }

    /**
     * Set the email recipients.
     *
     * @param array $recipients
     *
     * @return static
     */
    public function recipients(array $recipients)
    {
        $this->recipients = collect($recipients);

        return $this;
    }

    /**
     * Set the variable resolver.
     *
     * @param VariableResolver $resolver
     *
     * @return static
     */
    public function resolver(VariableResolver $resolver)
    {
        $this->resolver = $resolver;

        return $this;
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
        $chunks = $this->recipients->chunk($size);

        return $chunks->map([$this, 'build'])->toArray();
    }

    /**
     * Send the email.
     *
     * @param Collection $recipients
     *
     * @return mixed
     */
    public function send(Collection $recipients = null)
    {
        if (is_null($recipients)) {
            $recipients = $this->recipients;
        }

        return $this->mailer->send($this->build($recipients));
    }

    /**
     * Chunk recipients before sending.
     *
     * @param int $size
     *
     * @return \Generator
     */
    public function sendChunk(int $size = 1000)
    {
        foreach ($this->recipients->chunk($size) as $chunk) {
            yield $this->send($chunk);
        }
    }

    /**
     * Build the email to be sent.
     *
     * @param Collection|null $recipients
     *
     * @return array
     */
    protected function build(Collection $recipients): array
    {
        return array_merge([
            'template' => $this->template,
            'recipients' => $this->buildRecipients($recipients),
            'variables' => $this->buildVariables($recipients),
        ], $this->attributes);
    }

    /**
     * Build the message recipients.
     *
     * @param Collection $recipients
     *
     * @return array
     */
    protected function buildRecipients(Collection $recipients): array
    {
        return $recipients->map(function (string $email) {
            return [
                'email' => $email,
                'type' => 'to'
            ];
        })->toArray();
    }

    /**
     * Build the variables for given collection of recipients.
     *
     * @param Collection $recipients
     *
     * @return array
     */
    protected function buildVariables(Collection $recipients): array
    {
        return $recipients->map(function (string $email) {
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