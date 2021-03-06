<?php

namespace Slides\Connector\Auth;

use Illuminate\Support\Facades\Redis;
use Illuminate\Redis\Connections\Connection;

/**
 * Class Cache
 *
 * @package Slides\Connector\Auth
 */
class Cache
{
    /**
     * Get a user parameter from cache.
     *
     * @param int $remoteId The remote user ID.
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function setUserParam(int $remoteId, string $key, $value)
    {
        $this->set('user:' . $remoteId, $key, $value);
    }

    /**
     * Get a user parameter from cache.
     *
     * @param int $remoteId The remote user ID.
     * @param string $key
     *
     * @return mixed
     */
    public function getUserParam(int $remoteId, string $key)
    {
        return $this->get('user:' . $remoteId, $key);
    }

    /**
     * Get all parameters related to the user.
     *
     * @param int $remoteId The remote user ID.
     *
     * @return array
     */
    public function getUserParams(int $remoteId): array
    {
        return $this->getAll('user:' . $remoteId);
    }

    /**
     * Set a parameter value to cache.
     *
     * @param string $key
     * @param string|null $field
     * @param mixed $value
     *
     * @return void
     */
    public function set(string $key, ?string $field, $value)
    {
        $value = $this->castValueToString($value);

        \Illuminate\Support\Facades\Log::debug(
            '[Connector] Storing a parameter in cache',
            compact('key', 'field', 'value')
        );

        $this->ensure(function(Connection $redis) use ($key, $field, $value) {
            $field
                ? $redis->hset('connector:' . $key, $field, $value)
                : $redis->set('connector:' . $key, $value);
        });
    }

    /**
     * Get a parameter value from cache.
     *
     * @param string $key
     * @param string $field
     *
     * @return mixed
     */
    public function get(string $key, string $field = null)
    {
        $output = $this->ensure(function(Connection $redis) use ($key, $field) {
            return $field
                ? $redis->hget('connector:' . $key, $field)
                : $redis->get('connector:' . $key);
        }, function() {
            return null;
        });

        \Illuminate\Support\Facades\Log::debug(
            '[Connector] Getting a parameter from cache',
            compact('key', 'field', 'output')
        );

        return $this->castValueFromString($output);
    }

    /**
     * Get parameters from a hash.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getAll(string $key)
    {
        return $this->ensure(function(Connection $redis) use ($key) {
            return $redis->hgetall('connector:' . $key);
        }, function() {
            return [];
        });
    }

    /**
     * Catch an exception and prevent application from breaking while interacting with Redis.
     *
     * @param \Closure $callback
     * @param \Closure|null $fallback
     *
     * @return mixed|false
     */
    protected function ensure(\Closure $callback, \Closure $fallback = null)
    {
        try {
            $output = $callback(Redis::connection('authService'));
        }
        catch(\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());

            if($fallback instanceof \Closure) {
                $output = $fallback($e);
            }
        }

        return $output ?? false;
    }

    /**
     * Case value to string.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function castValueToString($value)
    {
        if(is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value;
    }

    /**
     * Case value from string.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function castValueFromString($value)
    {
        if($value === 'true' || $value === 'false') {
            return $value === 'true' ? true : false;
        }

        if($value === '') {
            return null;
        }

        return $value;
    }
}