<?php

namespace Slides\Connector\Auth\Commands;

use Slides\Connector\Auth\AuthService;
use Slides\Connector\Auth\Sync\Syncable;
use Slides\Connector\Auth\Client as AuthClient;
use Slides\Connector\Auth\Sync\User as SyncUser;
use Illuminate\Support\Facades\Auth;

/**
 * Class SyncUsers
 *
 * @package Slides\Connector\Auth\Commands
 */
class SyncUsers extends \Illuminate\Console\Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connector:sync-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize users with the remote authentication service';

    /**
     * @var AuthClient
     */
    protected $authClient;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * SyncUsers constructor.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->authClient = new AuthClient();

        $users = Auth::getProvider()->createModel()
            ->newQuery()
            ->get();

        if(!$this->confirm('There are ' . $users->count() . ' users to sync. Continue?')) {
            return;
        }

        $response = $this->authClient->request('sync', ['users' => $this->formatUsers($users)]);
        $difference = $response['difference'];
        $remoteStats = $response['stats'];

        $this->writeStats('Remote affection', array_keys($remoteStats), array_values($remoteStats));

        if(!count($difference)) {
            $this->info('There are no remote changes!');
        }
        else {
            $this->info('Remote changes detected, applying...');
        }

        $localStats = $this->applyDifference($difference);

        $this->writeStats('Local affection', array_keys($localStats), array_values($localStats));
    }

    /**
     * Format users
     *
     * @param Syncable[]|\Illuminate\Database\Eloquent\Collection $users
     *
     * @return array
     */
    protected function formatUsers($users): array
    {
        return $users
            ->map(function(Syncable $user) {
                return [
                    'id' => $user->retrieveId(),
                    'name' => $user->retrieveName(),
                    'email' => $user->retrieveEmail(),
                    'password' => $user->retrievePassword(),
                    'created_at' => $user->retrieveCreatedAt()->toDateTimeString(),
                    'updated_at' => $user->retrieveUpdatedAt()->toDateTimeString()
                ];
            })
            ->toArray();
    }

    /**
     * Apply the difference
     *
     * @param array $users
     *
     * @return array
     */
    protected function applyDifference(array $users): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'deleted' => 0];

        foreach ($this->loadUsers($users) as $user) {
            switch ($user->getRemoteAction()) {
                case 'create': {
                    $this->createUser($user);
                    $stats['created']++;
                    break;
                }
                case 'update': {
                    $this->updateUser($user);
                    $stats['updated']++;
                    break;
                }
                case 'delete': {
                    $this->deleteUser($user);
                    $stats['deleted']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Write stats
     *
     * @param string $title
     * @param array $keys
     * @param array $values
     */
    private function writeStats(string $title, array $keys, array $values)
    {
        $this->output->title($title);
        $this->output->table($keys, array($values));
    }

    /**
     * Create a local user
     *
     * @param SyncUser $user
     *
     * @throws
     */
    private function createUser(SyncUser $user)
    {
        $email = $user->getEmail();

        // If a user already exists, skip the process
        if(Auth::getProvider()->retrieveByCredentials(['email' => $email])) {
            $this->warn("User with email {$email} already exists, unable to create");

            return;
        }

        $this->authService->handle(AuthService::HANDLER_USER_SYNC_CREATE, ['remote' => $user]);
    }

    /**
     * Update a local user
     *
     * @param SyncUser $user
     *
     * @throws
     */
    private function updateUser(SyncUser $user)
    {
        $email = $user->getEmail();

        // If a user doesn't exist, skip the process
        if(!$model = Auth::getProvider()->retrieveByCredentials(['email' => $email])) {
            $this->warn("User with email {$email} doesn't exist, unable to update");

            return;
        }

        $this->authService->handle(AuthService::HANDLER_USER_SYNC_UPDATE, ['remote' => $user, 'local' => $model]);
    }

    /**
     * Delete a local user
     *
     * @param SyncUser $user
     *
     * @throws
     */
    private function deleteUser(SyncUser $user)
    {
        $email = $user->getEmail();

        // If a user doesn't exist, skip the process
        if(!$model = Auth::getProvider()->retrieveByCredentials(['email' => $email])) {
            $this->warn("User with email {$email} doesn't exist, unable to delete");

            return;
        }

        $this->authService->handle(AuthService::HANDLER_USER_SYNC_DELETE, ['remote' => $user, 'local' => $model]);
    }

    /**
     * Parse user into entities
     *
     * @param array $users
     *
     * @return SyncUser[]|array
     */
    protected function loadUsers(array $users): array
    {
        return array_map(function(array $user) {
            return new SyncUser(
                array_get($user, 'name'),
                array_get($user, 'email'),
                array_get($user, 'password'),
                array_get($user, 'updated_at'),
                array_get($user, 'created_at'),
                array_get($user, 'action')
            );
        }, $users);
    }
}