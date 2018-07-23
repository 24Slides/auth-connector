<?php

namespace Slides\Connector\Auth\Concerns;

use Slides\Connector\Auth\Notifications\ResetPasswordNotification;

/**
 * Trait UserHelpers
 *
 * @package Slides\Connector\Auth\Concerns
 */
trait UserHelpers
{
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
     * Send the password reset notification.
     *
     * @param string $token
     *
     * @return void
     */
    public function sendPasswordResetNotification(string $token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}