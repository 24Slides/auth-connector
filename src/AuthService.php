<?php

namespace Slides\Connector\Auth;

use Illuminate\Support\Facades\DB;

/**
 * Class Service
 *
 * @package Slides\Connector\Auth
 */
class AuthService
{
    const HANDLER_USER_CREATE      = 'user.create';
    const HANDLER_USER_UPDATE      = 'user.update';
    const HANDLER_USER_DELETE      = 'user.delete';
    const HANDLER_USER_SYNC_CREATE = 'sync.user.create';
    const HANDLER_USER_SYNC_UPDATE = 'sync.user.update';
    const HANDLER_USER_SYNC_DELETE = 'sync.user.delete';

    /**
     * Application handlers
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * Create a handler
     *
     * @param string $key
     * @param \Closure $callback
     *
     * @return $this
     */
    public function makeHandler(string $key, \Closure $callback)
    {
        $this->handlers[$key] = $callback;

        return $this;
    }

    /**
     * Run a handler
     *
     * @param string $key
     * @param array $parameters
     * @param \Closure|null $fallback
     *
     * @return mixed
     *
     * @throws
     */
    public function runHandler(string $key, array $parameters, \Closure $fallback = null)
    {
        $handler = $this->getHandler($key);

        if(!$handler || !$handler instanceof \Closure) {
            throw new \InvalidArgumentException("Handler `{$key}` cannot be found");
        }

        return $this->ensure(function() use ($handler, $parameters) {
            return call_user_func_array($handler, ['parameters' => $parameters]);
        }, $fallback);
    }

    /**
     * Set application handlers
     *
     * @param array $handlers
     */
    public function setHandlers(array $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * Get application handlers
     *
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Get a handler
     *
     * @param string $handler
     *
     * @return \Closure|null
     */
    public function getHandler(string $handler)
    {
        return array_get($this->handlers, $handler);
    }

    /**
     * Performs a callback logic within database transaction.
     *
     * @param \Closure $callback
     * @param \Closure|null $fallback The callback which should fired when exception throws.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function ensure(\Closure $callback, \Closure $fallback = null)
    {
        DB::beginTransaction();

        try {
            $output = $callback();
        }
        catch(\Exception $e) {
            DB::rollBack();

            if(is_null($fallback)) {
                throw $e;
            }

            $output = $fallback();
        }

        DB::commit();

        return $output;
    }
}