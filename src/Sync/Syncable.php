<?php

namespace Slides\Connector\Auth\Sync;

/**
 * Interface Syncable
 *
 * @package Slides\Connector\Auth\Sync
 */
interface Syncable
{
    /**
     * Retrieve a user's ID.
     *
     * @return int
     */
    public function retrieveId();

    /**
     * Retrieve user's remote ID
     *
     * @return int
     */
    public function retrieveRemoteId();

    /**
     * Retrieve a user's name.
     *
     * @return string|null
     */
    public function retrieveName();

    /**
     * Retrieve a user's email.
     *
     * @return string
     */
    public function retrieveEmail();

    /**
     * Retrieve user's country code in ISO 3166-1 alpha-2 representation
     *
     * @return string|null
     */
    public function retrieveCountry();

    /**
     * Retrieve a user's hashed password.
     *
     * @return string|null
     */
    public function retrievePassword();

    /**
     * Retrieve a user's created_at column.
     *
     * @return \Carbon\Carbon
     */
    public function retrieveCreatedAt();

    /**
     * Retrieve a user's updated_at column.
     *
     * @return \Carbon\Carbon|null
     */
    public function retrieveUpdatedAt();

    /**
     * Retrieve a user's deleted_at column.
     *
     * @return \Carbon\Carbon|null
     */
    public function retrieveDeletedAt();
}