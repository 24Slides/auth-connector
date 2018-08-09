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
     * Retrieve user's name
     *
     * @return string|null
     */
    public function retrieveName();

    /**
     * Retrieve user's email
     *
     * @return string
     */
    public function retrieveEmail();

    /**
     * Retrieve user's country code in ISO 3166-1 alpha-2 representation
     *
     * @return string
     */
    public function retrieveCountry();

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
     * @return \Carbon\Carbon|null
     */
    public function retrieveUpdatedAt();
}