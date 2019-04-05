<?php

namespace Slides\Connector\Auth\Services\Assessment;

use Illuminate\Support\Collection;
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
        $localKeys = $this->users->findRemoteIds();

        return [
            'uniqueTenantUsers' => $this->retrieveUniqueLocals($keys, $localKeys),
            'uniqueServiceUserKeys' => $this->retrieveUniqueRemotes($keys, $localKeys)
        ];
    }

    /**
     * Retrieve remote users that not exist locally.
     *
     * @param array $remoteKeys
     * @param Collection $localKeys
     *
     * @return array
     */
    protected function retrieveUniqueRemotes(array $remoteKeys, Collection $localKeys): array
    {
        return array_diff($remoteKeys, $localKeys->toArray());
    }

    /**
     * Retrieve local users that not exist remotely.
     *
     * @param array $remoteKeys
     * @param Collection $localKeys
     *
     * @return Collection
     */
    protected function retrieveUniqueLocals(array $remoteKeys, Collection $localKeys): Collection
    {
        $uniqueKeys = $localKeys->diff($remoteKeys);

        return $this->users->many($uniqueKeys->keys()->all());
    }
}