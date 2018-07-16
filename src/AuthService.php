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
    const HANDLER_USER_CREATE      = 'create';
    const HANDLER_USER_UPDATE      = 'update';
    const HANDLER_USER_DELETE      = 'delete';
    const HANDLER_USER_SYNC_CREATE = 'sync.create';
    const HANDLER_USER_SYNC_UPDATE = 'sync.update';
    const HANDLER_USER_SYNC_DELETE = 'sync.delete';

    /**
     * The class with handler methods
     *
     * @var object
     */
    protected $handlersContainer;

    /**
     * Load handlers from the given container.
     *
     * @param $container
     *
     * @return void
     */
    public function loadHandlers($container)
    {
        $this->handlersContainer = $container;
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
     * @throws \Exception
     */
    public function handle(string $key, array $parameters, \Closure $fallback = null)
    {
        $handler = camel_case(str_replace('.', ' ', $key));

        if(!method_exists($this->handlersContainer, $handler)) {
            throw new \InvalidArgumentException("Handler `{$handler}` cannot be found");
        }

        return $this->ensure(function() use ($handler, $parameters) {
            return call_user_func_array([$this->handlersContainer, $handler], $parameters);
        }, $fallback);
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