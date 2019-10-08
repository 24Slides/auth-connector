<?php

namespace Slides\Connector\Auth\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Class UserRepository
 *
 * @package Slides\Connector\Auth\Repositories
 */
class UserRepository
{
    /**
     * Retrieve the table name.
     *
     * @return string
     */
    public function table()
    {
        return Auth::getProvider()
            ->createModel()
            ->getTable();
    }

    /**
     * Create a new query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return Auth::getProvider()
            ->createModel()
            ->newQuery();
    }

    /**
     * Returns all remote IDs from users table
     *
     * @return \Illuminate\Support\Collection
     */
    public function findRemoteIds()
    {
        return \Illuminate\Support\Facades\DB::table($this->table())->pluck('remote_id', 'id');
    }

    /**
     * Returns users with given IDs
     *
     * @param array $ids List of entity IDs to be queried
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model[]
     */
    public function many(array $ids)
    {
        return $this->query()
            ->whereKey($ids)
            ->get();
    }
}