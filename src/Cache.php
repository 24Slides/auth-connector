<?php

namespace Slides\Connector\Auth;

use Illuminate\Support\Facades\Redis;

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
        $this->set('user:' . $remoteId . $key, $value);
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
        return $this->get('user:' . $remoteId . $key);
    }

    /**
     * Set a parameter value to cache.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function set(string $key, $value)
    {
        try {
            Redis::connection('authService')->set('connector:' . $key, $value);
        }
        catch(\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
        }
    }

    /**
     * Get a parameter value from cache.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        try {
            Redis::connection('authService')->get('connector:' . $key);
        }
        catch(\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
            return;
        }

        return null;
    }
}