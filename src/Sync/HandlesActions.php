<?php

namespace Slides\Connector\Auth\Sync;

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
        if(Auth::getProvider()->retrieveByCredentials(['email' => $remote->getEmail()])) {
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
        if(!$local = Auth::getProvider()->retrieveByCredentials(['email' => $remote->getEmail()])) {
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
}