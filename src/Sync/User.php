<?php

namespace Slides\Connector\Auth\Sync;

use Carbon\Carbon;

/**
 * Class User describes a remote user
 *
 * @package Slides\Connector\Auth\Sync
 */
final class User
{
    /**
     * User's remote ID
     *
     * @var int
     */
    protected $remoteId;

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
     * @param int $remoteId
     * @param string|null $name
     * @param string $email
     * @param string|null $password
     * @param string|null $updated
     * @param string $created
     * @param string $action
     */
    public function __construct(
        int $remoteId,
        ?string $name,
        string $email,
        ?string $password,
        ?string $updated,
        string $created,
        string $action = null
    )
    {
        $this->remoteId = $remoteId;
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
     * @return string|null
     */
    public function getRemoteAction(): ?string
    {
        return $this->remoteAction;
    }

    /**
     * Get remote user ID
     *
     * @return int
     */
    public function getRemoteId(): int
    {
        return $this->remoteId;
    }

    /**
     * Reset the password.
     *
     * @return void
     */
    public function resetPassword()
    {
        $this->password = null;
    }

    /**
     * Create a user from the response
     *
     * @param array $response
     *
     * @return static
     */
    public static function createFromResponse(array $response)
    {
        $user = array_get($response, 'user');

        return new static(
            array_get($user, 'id'),
            array_get($user, 'name'),
            array_get($user, 'email'),
            array_get($user, 'password'),
            array_get($user, 'updated_at'),
            array_get($user, 'created_at')
        );
    }
}