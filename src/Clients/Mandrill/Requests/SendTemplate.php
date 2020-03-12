<?php

namespace Slides\Connector\Auth\Clients\Mandrill\Requests;

use Illuminate\Support\Arr;
use Slides\Connector\Auth\Clients\AbstractRequest;

/**
 * Class SendTemplate
 *
 * @property string $template
 * @property array $recipients
 * @property array $variables
 * @property array $from
 * @property string $subject
 *
 * @package Slides\Connector\Auth\Clients\Mandrill\Requests
 */
class SendTemplate extends AbstractRequest
{
    /**
     * HTTP method
     *
     * @var string
     */
    protected $method = 'post';

    /**
     * Request URI
     *
     * @var string
     */
    protected $uri = 'messages/send-template.json';

    /**
     * Validate a response
     *
     * @return bool
     */
    public function success(): bool
    {
        return $this->client->getResponse()->getStatusCode() === 200;
    }

    /**
     * Compose a request
     *
     * @return void
     */
    public function compose()
    {
        $optional = [
            'from_email' => Arr::get($this->from, 'email'),
            'from_name' => Arr::get($this->from, 'name'),
            'subject' => $this->subject,
        ];

        $this->body([
            'template_name' => $this->template,
            'template_content' => [],
            'message' => array_merge([
                'preserve_recipients' => false,
                'track_opens' => true,
                'track_clicks' => true,
                'to' => $this->recipients,
                'merge_vars' => $this->variables,
                'merge_language' => 'handlebars'
            ], array_filter($optional))
        ]);
    }
}