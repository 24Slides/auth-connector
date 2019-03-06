<?php

namespace Slides\Connector\Auth\Services\Assessment;

use Slides\Connector\Auth\Repositories\UserRepository;

/**
 * Class AssessmentService
 *
 * @package Slides\Connector\Auth\Services\Assessment
 */
class AssessmentService
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * AssessmentService constructor.
     *
     * @param UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * Differentiate users with the passed ones.
     *
     * @param array $keys The remote user keys.
     *
     * @return array
     */
    public function differentiateUsers(array $keys): array
    {
        return [
            'uniqueTenantUsers' => $this->retrieveUniqueLocals($keys),
            'uniqueServiceUserKeys' => $this->retrieveUniqueRemotes($keys)
        ];
    }

    /**
     * Retrieve remote users that not exist locally.
     *
     * @param array $remoteKeys
     *
     * @return \Illuminate\Support\Collection
     */
    protected function retrieveUniqueRemotes(array $remoteKeys)
    {
        return $this->users->withTemporaryTable('unknownRemotesTable',
            function(\Illuminate\Database\Schema\Blueprint $table) {
                $table->bigInteger('id');
            },
            array_map(function($key) {
                return ['id' => $key];
            }, $remoteKeys),
            function(\Illuminate\Database\Eloquent\Builder $query, $table) {
                return $query->rightJoin($table, $table . '.id', $this->users->table() . '.remote_id')
                    ->whereNull($this->users->table() . '.id')
                    ->pluck($table . '.id');
            }
        );
    }

    /**
     * Retrieve local users that not exist remotely.
     *
     * @param array $remoteKeys
     *
     * @return \Illuminate\Support\Collection
     */
    protected function retrieveUniqueLocals(array $remoteKeys)
    {
        return $this->users->query()
            ->whereNotIn('remote_id', $remoteKeys)
            ->orWhereNull('remote_id')
            ->get();
    }
}