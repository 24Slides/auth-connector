<?php

namespace Slides\Connector\Auth\Sync;

use Illuminate\Support\Collection;

/**
 * Class UserGroupsIterator
 *
 * @package Slides\Connector\Auth\Sync
 */
class UserGroupsIterator implements \Iterator
{
    /**
     * The users collection.
     *
     * @var Collection
     */
    protected $users;

    /**
     * Number of users which can be sent per request
     *
     * @var int
     */
    protected $perRequest;

    /**
     * The iterator position
     *
     * @var int
     */
    protected $position;

    /**
     * UserGroupsIterator constructor.
     *
     * @param Collection $users
     * @param int $perRequest
     */
    public function __construct(Collection $users, int $perRequest)
    {
        $this->users = $users;
        $this->perRequest = $perRequest;
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->position = 1;
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return $this->current()->isNotEmpty();
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return $this->users->forPage($this->position, $this->perRequest);
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Calculate a number of requests.
     *
     * @return int
     */
    public function requestsCount(): int
    {
        return max(1, ceil($this->users->count() / $this->perRequest));
    }
}