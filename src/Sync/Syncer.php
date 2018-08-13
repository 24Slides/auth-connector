<?php

namespace Slides\Connector\Auth\Sync;

use Illuminate\Support\Collection;
use Slides\Connector\Auth\Sync\Syncable as LocalUser;
use Slides\Connector\Auth\Sync\User as RemoteUser;
use Slides\Connector\Auth\Client;
use Slides\Connector\Auth\AuthService;

/**
 * Class Syncer
 *
 * @package Slides\Connector\Auth\Sync
 */
class Syncer
{
    use HandlesActions;

    /**
     * Synchronization modes.
     *
     * `passwords` — allows updating passwords locally and remotely.
     */
    const MODE_PASSWORDS = 'passwords';

    /**
     * The authentication service.
     *
     * @var AuthService
     */
    protected $authService;

    /**
     * Authentication Service client.
     *
     * @var Client
     */
    protected $client;

    /**
     * The local users for syncing remotely.
     *
     * @var LocalUser[]|Collection
     */
    protected $locals;

    /**
     * The remote users fetched.
     *
     * @var RemoteUser[]|Collection
     */
    protected $foreigners;

    /**
     * The sync modes.
     *
     * @var array
     */
    protected $modes;

    /**
     * The remote statistics.
     *
     * @var array
     */
    protected $remoteStats = [
        'created' => 0,
        'updated' => 0,
        'deleted' => 0
    ];

    /**
     * The local statistics.
     *
     * @var array
     */
    protected $localStats = [
        'created' => 0,
        'updated' => 0,
        'deleted' => 0
    ];

    /**
     * Syncer constructor.
     *
     * @param LocalUser[]|Collection|null $locals
     * @param array $modes
     * @param Client|null $client
     */
    public function __construct(Collection $locals = null, array $modes = [], Client $client = null)
    {
        $this->locals = $locals ?? collect();
        $this->modes = $modes;
        $this->client = $client ?? new Client();
        $this->authService = app('authService');
    }

    /**
     * Synchronize local users remotely and apply changes.
     *
     * @return void
     */
    public function sync()
    {
        $response = $this->client->request('sync', [
            'users' => $this->formatLocals(),
            'modes' => $this->modes
        ]);

        $this->parseResponse($response);

        $this->apply();
    }

    /**
     * Parse a response.
     *
     * @param array $response
     */
    protected function parseResponse(array $response)
    {
        $foreigners = array_map(function (array $user) {
            return $this->createRemoteUserFromResponse($user);
        }, array_get($response, 'difference'));

        $this->remoteStats = array_get($response, 'stats');
        $this->foreigners = collect($foreigners);
    }

    /**
     * Apply changes locally from the given response.
     *
     * @return void
     */
    public function apply()
    {
        foreach ($this->foreigners as $foreigner) {
            try {
                $this->handleAction(
                    $foreigner,
                    $action = $foreigner->getRemoteAction()
                );
            }
            catch(\Slides\Connector\Auth\Exceptions\SyncException $e) {
                \Illuminate\Support\Facades\Log::error(
                    "Cannot $action the user {$foreigner->getEmail()}: " . $e->getMessage()
                );
            }
        }
    }

    /**
     * Increment a local stats value.
     *
     * @param string $key
     */
    private function incrementStats(string $key)
    {
        $value = array_get($this->localStats, $key, 0);

        $this->localStats[$key] = ++$value;
    }

    /**
     * Format local users for a request payload.
     *
     * @return array
     */
    private function formatLocals()
    {
        return $this->locals
            ->map(function(Syncable $user) {
                return [
                    'id' => $user->retrieveId(),
                    'remoteId' => $user->retrieveRemoteId(),
                    'name' => $user->retrieveName(),
                    'email' => $user->retrieveEmail(),
                    'password' => $user->retrievePassword(),
                    'country' => $user->retrieveCountry(),
                    'created_at' => $user->retrieveCreatedAt()->toDateTimeString(),
                    'updated_at' => $user->retrieveUpdatedAt()->toDateTimeString()
                ];
            })
            ->toArray();
    }

    /**
     * Create a remote user from the response.
     *
     * @param array $user
     *
     * @return User
     */
    public function createRemoteUserFromResponse(array $user)
    {
        return new RemoteUser(
            array_get($user, 'id'),
            array_get($user, 'name'),
            array_get($user, 'email'),
            array_get($user, 'password'),
            array_get($user, 'updated_at'),
            array_get($user, 'created_at'),
            array_get($user, 'country'),
            array_get($user, 'action')
        );
    }

    /**
     * Check whether a mode is enabled.
     *
     * @param string $mode
     *
     * @return bool
     */
    public function hasMode(string $mode): bool
    {
        return in_array($mode, $this->modes);
    }

    /**
     * Check if there are difference detected by remote service.
     *
     * @return bool
     */
    public function hasDifference(): bool
    {
        return $this->foreigners->isNotEmpty();
    }

    /**
     * Get the local stats.
     *
     * @return array
     */
    public function getLocalStats(): array
    {
        return $this->localStats;
    }

    /**
     * Get the remote stats.
     *
     * @return array
     */
    public function getRemoteStats(): array
    {
        return $this->remoteStats;
    }

    /**
     * Set remote users.
     *
     * @param Collection|RemoteUser[] $foreigners
     */
    public function setForeigners($foreigners): void
    {
        $this->foreigners = $foreigners;
    }

    /**
     * Retrieve all local users.
     *
     * @return Collection
     */
    public static function retrieveLocals(): Collection
    {
        return \Illuminate\Support\Facades\Auth::getProvider()->createModel()
            ->newQuery()
            ->get();
    }
}