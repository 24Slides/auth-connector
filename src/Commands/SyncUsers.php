<?php

namespace Slides\Connector\Auth\Commands;

use Slides\Connector\Auth\AuthService;
use Slides\Connector\Auth\Sync\Syncable;
use Illuminate\Contracts\Auth\UserProvider;
use Slides\Connector\Auth\Client as AuthClient;
use Slides\Connector\Auth\Sync\User as SyncUser;

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
     * @var UserProvider|\Illuminate\Auth\EloquentUserProvider
     */
    protected $userProvider;

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
     * @param UserProvider $userProvider
     * @param AuthService $authService
     */
    public function __construct(UserProvider $userProvider, AuthService $authService)
    {
        $this->userProvider = $userProvider;
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

        $users = $this->userProvider->createModel()
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

        foreach ($users as $user) {
            switch ($user['action']) {
                case 'create': {
                    $this->createUser($user);
                    $stats['created']++;
                    break;
                }
                case 'update': {
                    $this->updateUser($user);
                    $stats['updated']++;
                }
                case 'delete': {
                    // $this->deleteUser($user);
                    // $stats['deleted']++;
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
     */
    private function createUser(SyncUser $user)
    {
        $email = $user->getEmail();

        // If a user already exists, skip the process
        if($model = $this->userProvider->retrieveByCredentials(['email' => $email])) {
            $this->warn("User with email {$email} already exists, unable to create");

            return;
        }

        $this->authService->runHandler(AuthService::HANDLER_USER_SYNC_CREATE, ['user' => $user]);
    }

    /**
     * Update a local user
     *
     * @param SyncUser $user
     */
    private function updateUser(SyncUser $user)
    {
        $email = $user->getEmail();

        // If a user doesn't exist, skip the process
        if(!$model = $this->userProvider->retrieveByCredentials(['email' => $email])) {
            $this->warn("User with email {$email} doesn't exist, unable to update");

            return;
        }

        $this->authService->runHandler(AuthService::HANDLER_USER_SYNC_UPDATE, ['user' => $user]);
    }

    /**
     * Delete a local user
     *
     * @param SyncUser $user
     */
    private function deleteUser(SyncUser $user)
    {
        $email = $user->getEmail();

        // If a user doesn't exist, skip the process
        if(!$model = $this->userProvider->retrieveByCredentials(['email' => $email])) {
            $this->warn("User with email {$email} doesn't exist, unable to delete");

            return;
        }

        $this->authService->runHandler(AuthService::HANDLER_USER_SYNC_DELETE, ['user' => $user]);
    }
}