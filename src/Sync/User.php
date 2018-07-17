<?php

namespace Slides\Connector\Auth\Sync;

use Carbon\Carbon;

/**
 * Class User
 *
 * @package Slides\Connector\Auth\Sync
 */
final class User
{
    /**
     * User's name
     *
     * @var string
     */
    protected $name;

    /**
     * User's email
     *
     * @var string
     */
    protected $email;

    /**
     * User's encrypted password
     *
     * @var string
     */
    protected $password;

    /**
     * @var \Carbon\Carbon
     */
    protected $updated;

    /**
     * @var \Carbon\Carbon
     */
    protected $created;

    /**
     * The instance of the user locally
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $localUser;

    /**
     * Whether a user exist only locally
     *
     * @var bool
     */
    protected $isLocal = false;

    /**
     * The action for a remote sync client
     *
     * @var string
     */
    protected $remoteAction;

    /**
     * User constructor.
     *
     * @param string|null $name
     * @param string $email
     * @param string|null $password
     * @param string|null $updated
     * @param string $created
     * @param string $action
     */
    public function __construct(
        ?string $name,
        string $email,
        ?string $password,
        ?string $updated,
        string $created,
        string $action
    )
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->updated = new Carbon($updated);
        $this->created = new Carbon($created);
        $this->remoteAction = $action;
    }

    /**
     * Get user's name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get user's email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get user's password
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Get created_at date
     *
     * @return Carbon
     */
    public function getCreated(): Carbon
    {
        return $this->created;
    }

    /**
     * Get updated_at date
     *
     * @return Carbon|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get remote action
     *
     * @return string
     */
    public function getRemoteAction(): string
    {
        return $this->remoteAction;
    }
}