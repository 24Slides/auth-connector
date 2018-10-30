<?php

namespace Slides\Connector\Auth\Concerns;

use Slides\Connector\Auth\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Passwords\CanResetPassword;

/**
 * Trait UserHelpers
 *
 * @package Slides\Connector\Auth\Concerns
 */
trait UserHelpers
{
    use CanResetPassword;

    /**
     * @inheritdoc
     */
    public function retrieveId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function retrieveRemoteId()
    {
        return $this->remote_id;
    }

    /**
     * @inheritdoc
     */
    public function retrieveName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function retrieveEmail()
    {
        return $this->email;
    }

    /**
     * @inheritdoc
     */
    public function retrievePassword()
    {
        return $this->password;
    }

    /**
     * @inheritdoc
     */
    public function retrieveCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @inheritdoc
     */
    public function retrieveUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @inheritdoc
     */
    public function retrieveDeletedAt()
    {
        return $this->getDeletedAtColumn();
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     *
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}