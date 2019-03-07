<?php

namespace Slides\Connector\Auth\Repositories;

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
     * Make a query using a temporary table.
     *
     * @param string $name
     * @param \Closure $table
     * @param \Closure $query
     *
     * @return mixed
     */
    public function withTemporaryTable(string $name, \Closure $table, \Closure $query)
    {
        \Illuminate\Support\Facades\Schema::create($name, function(\Illuminate\Database\Schema\Blueprint $callback) use ($table) {
            $table($callback);
            $callback->temporary();
        });

        $results = $query(\Illuminate\Support\Facades\DB::table($name), $name);

        \Illuminate\Support\Facades\Schema::dropIfExists($name);

        return $results;
    }
}