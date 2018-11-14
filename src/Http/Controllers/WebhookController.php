<?php

namespace Slides\Connector\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Slides\Connector\Auth\Webhooks\Dispatcher;

/**
 * Class WebhookController
 *
 * @package Slides\Connector\Auth\Http\Controllers
 */
class WebhookController extends \Illuminate\Routing\Controller
{
    /**
     * The webhook dispatcher.
     *
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * WebhookController constructor.
     *
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handle the incoming webhook.
     *
     * @param string $key
     * @param Request $request
     *
     * @throws \Slides\Connector\Auth\Exceptions\WebhookValidationException
     */
    public function __invoke(string $key, Request $request)
    {
        $this->dispatcher->handle($key, $request->all());
    }
}