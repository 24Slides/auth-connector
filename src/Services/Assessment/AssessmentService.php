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
        return $this->users->withTemporaryTable('remoteUserKeys',
            function(\Illuminate\Database\Schema\Blueprint $table) use ($remoteKeys) {
                $table->bigInteger('id');
            },
            function(\Illuminate\Database\Query\Builder $query, string $table) use ($remoteKeys) {
                // This is not a good approach, but using builder it significantly decreases performance,
                // because builder users collections all the time.
                \Illuminate\Support\Facades\DB::insert('INSERT INTO ' . $table . ' VALUES (' . implode('),(', $remoteKeys) . ')');

                return $query->whereNotIn('id', function(\Illuminate\Database\Query\Builder $subQuery) {
                    $subQuery->select('remote_id')
                        ->from($this->users->table());
                })->pluck('id');
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