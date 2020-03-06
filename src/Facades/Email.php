<?php

namespace Slides\Connector\Auth\Facades;

/**
 * Class Email
 *
 * @package Slides\Connector\Auth\Facades
 */
class Email extends \Illuminate\Support\Facades\Facade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return 'emailer';
    }
}