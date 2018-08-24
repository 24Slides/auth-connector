<?php

namespace Slides\Connector\Auth\Sync;

use Slides\Connector\Auth\Sync\Syncable as LocalUser;
use Slides\Connector\Auth\Sync\User as RemoteUser;
use Illuminate\Support\Facades\Auth;
use Slides\Connector\Auth\AuthService;

/**
 * Trait HandlesActions
 *
 * @property AuthService $authService
 *
 * @package Slides\Connector\Auth\Sync
 */
trait HandlesActions
{
    /**
     * Handle a user action.
     *
     * @param RemoteUser $remote
     * @param string $action
     *
     * @return void
     */
    protected function handleAction(RemoteUser $remote, string $action)
    {
        $handler = 'action' . studly_case($action);

        if(!method_exists($this, $handler)) {
            throw new \Slides\Connector\Auth\Exceptions\SyncException("User action handler {$handler} cannot be found.");
        }

        $this->{$handler}($remote);
    }

    /**
     * Handle the "create" action.
     *
     * @param RemoteUser $remote
     *
     * @return void
     */
    protected function actionCreate(RemoteUser $remote)
    {
        // If a user with the same email was found, we need to skip the process
        if(Auth::getProvider()->retrieveByCredentials(['email' => strtolower($remote->getEmail())])) {
            return;
        }

        $this->authService->handle(AuthService::HANDLER_USER_SYNC_CREATE, ['remote' => $remote]);

        $this->incrementStats('created');
    }

    /**
     * Handle the "update" action.
     *
     * @param RemoteUser $remote
     *
     * @return void
     */
    protected function actionUpdate(RemoteUser $remote)
    {
        // If a user with the same email cannot be found, we should skip the process
        if(!$local = Auth::getProvider()->retrieveByCredentials(['email' => $remote->getEmail()])) {
            return;
        }

        // If a local user was updated later than remote one, we should skip the process
        // Since we have a latest one
        if($this->localNewerThanRemote($local, $remote)) {
            return;
        }

        // If "password" mode is not enabled, we cannot update local passwords, so we simply resetting it
        if(!$this->hasMode(static::MODE_PASSWORDS)) {
            $remote->resetPassword();
        }

        $this->authService->handle(AuthService::HANDLER_USER_SYNC_UPDATE, [
            'remote' => $remote,
            'local' => $local
        ]);

        $this->incrementStats('updated');
    }

    /**
     * Handle the "delete" action.
     *
     * @param RemoteUser $remote
     *
     * @return void
     */
    protected function actionDelete(RemoteUser $remote)
    {
        if(!$local = Auth::getProvider()->retrieveByCredentials(['email' => $remote->getEmail()])) {
            return;
        }

        $this->authService->handle(AuthService::HANDLER_USER_SYNC_DELETE, ['remote' => $remote, 'local' => $local]);

        $this->incrementStats('deleted');
    }

    /**
     * Check whether remote user has updated earlier than local one.
     *
     * @param Syncable|\Illuminate\Contracts\Auth\Authenticatable $local
     * @param User $remote
     *
     * @return bool
     */
    private function localNewerThanRemote(LocalUser $local, RemoteUser $remote)
    {
        if(!$local->retrieveRemoteId()) {
            return false;
        }

        if(!$remoteUpdated = $remote->getUpdated()) {
            return false;
        }

        if(!$localUpdate = $local->retrieveUpdatedAt()) {
            return false;
        }

        return $remoteUpdated->lessThan($localUpdate);
    }
}