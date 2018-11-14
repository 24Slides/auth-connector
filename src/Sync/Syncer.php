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
    use HandlesActions,
        ExportsUsers,
        ImportsUsers;

    /**
     * Number of users which can be sent per request
     */
    const USERS_PER_REQUEST = 5000;

    /**
     * Synchronization modes.
     *
     * `passwords` — allows updating passwords locally and remotely.
     * `users` — allows syncing specific users only.
     */
    const MODE_PASSWORDS = 'passwords';
    const MODE_USERS = 'users';

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
     * Output messages.
     *
     * @var array
     */
    protected $output = [];

    /**
     * The callback called on adding a message to the output.
     *
     * @var \Closure
     */
    protected $outputCallback;

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
        $this->foreigners = collect();
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
        $iterator = new UserGroupsIterator($this->locals, static::USERS_PER_REQUEST);

        $this->outputMessage('Total requests: ' . $iterator->requestsCount());

        /** @var LocalUser[]|Collection $users */
        foreach ($iterator as $users) {
            $this->outputMessage('Sending a request with bunch of ' . $users->count() . ' users');

            $response = $this->client->request('sync', [
                'users' => $this->formatLocals($users),
                'modes' => $this->modes
            ]);

            $this->outputMessage('Parsing a response...');

            $this->parseResponse($response);
        }

        $this->outputMessage("Applying {$this->foreigners->count()} remote changes locally");

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

        $this->mergeRemoteStats($remoteStats = array_get($response, 'stats'));

        $this->outputMessage(
            'Remote affection:'
            . ' created ' . $remoteStats['created']
            . ', updated ' . $remoteStats['updated']
            . ', deleted ' . $remoteStats['deleted']
        );

        $this->foreigners = $this->foreigners->merge($foreigners);
    }

    /**
     * Apply changes locally from the given response.
     *
     * @return void
     */
    public function apply()
    {
        $count = $this->getForeignersCount();

        foreach ($this->foreigners as $index => $foreigner) {
            $index++;

            $this->outputMessage(
                "[$index of $count] Handling the action \"" . $foreigner->getRemoteAction() . '"'
                    . ' of ' . $foreigner->getName()
                    . ' (' . $foreigner->getEmail() . ')'
            );

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
     * Format local users for a request payload.
     *
     * @param Collection $locals
     *
     * @return array
     */
    private function formatLocals(Collection $locals)
    {
        return $locals
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
            array_get($user, 'deleted_at'),
            array_get($user, 'country'),
            array_get($user, 'action')
        );
    }

    /**
     * Check whether a mode is passed.
     *
     * @param string $mode
     *
     * @return bool
     */
    public function hasMode(string $mode): bool
    {
        return array_key_exists($mode, $this->modes);
    }

    /**
     * Get passed modes.
     *
     * @return array
     */
    public function getModes(): array
    {
        return $this->modes;
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
    public function setForeigners(Collection $foreigners): void
    {
        $this->foreigners = $foreigners;
    }

    /**
     * Get number of foreign users.
     *
     * @return int
     */
    public function getForeignersCount(): int
    {
        return $this->foreigners->count();
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

    /**
     * Merge the remote stats.
     *
     * @param array $stats
     */
    private function mergeRemoteStats(array $stats)
    {
        $this->remoteStats['created'] += $stats['created'];
        $this->remoteStats['updated'] += $stats['updated'];
        $this->remoteStats['deleted'] += $stats['deleted'];
    }

    /**
     * Add a message to the output
     *
     * @param string $message
     *
     * @return void
     */
    private function outputMessage(string $message)
    {
        $output[] = $message;

        if($this->outputCallback instanceof \Closure) {
            call_user_func($this->outputCallback, $message);
        }
    }

    /**
     * Set a callback which should be called on adding output message.
     *
     * @param \Closure $outputCallback
     *
     * @return void
     */
    public function setOutputCallback(\Closure $outputCallback): void
    {
        $this->outputCallback = $outputCallback;
    }

    /**
     * Increment a local stats value.
     *
     * @param string $key
     */
    protected function incrementStats(string $key)
    {
        $value = array_get($this->localStats, $key, 0);

        $this->localStats[$key] = ++$value;
    }
}