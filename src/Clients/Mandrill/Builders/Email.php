<?php

namespace Slides\Connector\Auth\Clients\Mandrill\Builders;

use Exception;
use Illuminate\Support\Collection;
use Slides\Connector\Auth\Clients\Mandrill\Contracts\VariableResolver;
use Slides\Connector\Auth\Clients\Mandrill\Mailer;

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
    protected Mailer $mailer;

    /**
     * The variable resolver.
     *
     * @var VariableResolver|null
     */
    protected ?VariableResolver $resolver = null;

    /**
     * The additional attributes that should be added to email.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * The list of context variables.
     *
     * @var array
     */
    protected array $context = [];

    /**
     * @var string
     */
    protected string $template;

    /**
     * @var array
     */
    protected array $variables = [];

    /**
     * @var Collection
     */
    protected Collection $recipients;

    /**
     * Email constructor.
     *
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
        $this->recipients = new Collection();
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
     * @param array|string $variables
     *
     * @return $this
     */
    public function variables($variables)
    {
        $this->variables = is_array($variables) ? $variables : func_get_args();

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
     * @param string|null $name
     *
     * @return static
     */
    public function from(string $email, ?string $name = null)
    {
        $this->attributes['from'] = compact('email', 'name');

        return $this;
    }

    /**
     * Set the email recipients.
     *
     * @param array|string $recipients
     *
     * @return static
     */
    public function recipients($recipients)
    {
        $this->recipients = collect(is_array($recipients) ? $recipients : func_get_args());

        return $this;
    }

    /**
     * Set email context.
     *
     * @param mixed $context
     *
     * @return static
     */
    public function context($context)
    {
        $this->context = is_array($context) ? $context : func_get_args();

        return $this;
    }

    /**
     * Set message tags.
     *
     * @param array|string $tags
     *
     * @return static
     */
    public function tags($tags)
    {
        $this->attributes['tags'] = is_array($tags) ? $tags : func_get_args();

        return $this;
    }

    /**
     * Send the email.
     *
     * @param Collection|null $recipients
     *
     * @return mixed
     */
    public function send(Collection $recipients = null)
    {
        return $this->mailer->send($this->build($recipients ?: $this->recipients));
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
        return $recipients->map(fn(string $email) => ['email' => $email, 'type' => 'to'])->all();
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
        return $recipients->map(fn(string $email) => ['rcpt' => $email, 'vars' => $this->userVariables($email)])->all();
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
        if (!$this->resolver) {
            $this->resolver = app(VariableResolver::class, ['emails' => $this->recipients, 'context' => $this->context]);
        }

        return array_map(fn(string $variable) => $this->resolver->resolve($variable, $email), $this->variables);
    }
}