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
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return 'mandrill';
    }
}