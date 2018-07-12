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
     * Retrieve user's ID
     *
     * @return string|null
     */
    public function retrieveId();

    /**
     * Retrieve user's name
     *
     * @return string|null
     */
    public function retrieveName();

    /**
     * Retrieve user's email
     *
     * @return string|null
     */
    public function retrieveEmail();

    /**
     * Retrieve user's hashed password
     *
     * @return string|null
     */
    public function retrievePassword();

    /**
     * Retrieve user's created_at column
     *
     * @return \Carbon\Carbon
     */
    public function retrieveCreatedAt();

    /**
     * Retrieve user's updated_at column
     *
     * @return \Carbon\Carbon
     */
    public function retrieveUpdatedAt();
}