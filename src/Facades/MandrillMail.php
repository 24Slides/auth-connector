<?php

namespace Slides\Connector\Auth\Facades;

/**
 * Class Email
 *
 * @package Slides\Connector\Auth\Facades
 */
class MandrillMail extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Slides\Connector\Auth\Clients\Mandrill\Mailer::class;
    }
}