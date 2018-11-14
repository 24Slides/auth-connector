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
        return $this->deleted_at;
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

    /**
     * Transform user's email to "deleted email".
     *
     * @param string $email
     * @param int $index
     *
     * @return string
     */
    public static function deleteEmail(string $email, int $index): string
    {
        if(strpos($email, ".{$index}.deleted" !== false)) {
            return $email;
        }

        return "{$email}.{$index}.deleted";
    }

    /**
     * Transform user's "deleted email" to an original one.
     *
     * @param string $email
     * @param int $index
     *
     * @return string
     */
    public static function restoreEmail(string $email, int $index): string
    {
        return str_replace(".{$index}.deleted", '', $email);
    }
}