<?php

namespace Slides\Connector\Auth\Facades;

/**
 * Class AuthService
 *
 * @package Slides\Connector\Auth\Facades
 */
class AuthService extends \Illuminate\Support\Facades\Facade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return 'authService';
    }
}