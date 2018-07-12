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
     * Get user's name
     *
     * @return string
     */
    public function getName(): string
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
     * @return string
     */
    public function getPassword(): string
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
     * @return Carbon
     */
    public function getUpdated(): Carbon
    {
        return $this->updated;
    }
}