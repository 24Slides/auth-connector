<?php

namespace Slides\Connector\Auth\Commands;

use Slides\Connector\Auth\AuthService;
use Slides\Connector\Auth\Client as AuthClient;
use Slides\Connector\Auth\Sync\Syncer;
use Slides\Connector\Auth\Helpers\ConsoleHelper;
use Slides\Connector\Auth\Concerns\PassesModes;

/**
 * Class SyncUsers
 *
 * @package Slides\Connector\Auth\Commands
 */
class SyncUsers extends \Illuminate\Console\Command
{
    use PassesModes;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connector:sync-users
                            {--passwords : Allow syncing passwords (can rewrite remotely and locally) }
                            {--users=    : Sync the specific users }';

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
        $this->displayModes();

        $syncer = new Syncer($locals = $this->syncingUsers(), $this->modes());

        if($locals->isEmpty()) {
            $this->info('No local users found.');
        }

        if(!$this->confirm('There are ' . $locals->count() . ' local user(s) to sync. Continue?', $this->option('no-interaction'))) {
            return;
        }

        $syncer->setOutputCallback(function(string $message) {
            $this->info('[Syncer] ' . $message);
        });

        $duration = $this->measure(function() use ($syncer) {
            $syncer->sync();
        });

        $this->writeStats('Remote changes', $syncer->getRemoteStats());
        $this->writeStats('Local changes', $syncer->getLocalStats());

        $this->info("Finished in {$duration}s.");
    }

    /**
     * Output the stats.
     *
     * @param string $title
     * @param array $stats
     */
    private function writeStats(string $title, array $stats)
    {
        $this->output->title($title);
        $this->output->table(array_keys($stats), array(array_values($stats)));
    }

    /**
     * Measure an execution time of the callback.
     *
     * @param \Closure $callback
     *
     * @return float
     */
    public function measure(\Closure $callback)
    {
        $start = microtime(true);

        $callback();

        $end = microtime(true);

        return round($end - $start, 2);
    }

    /**
     * Retrieve users to sync.
     *
     * @return \Illuminate\Support\Collection
     */
    private function syncingUsers()
    {
        if(!$this->hasMode(Syncer::MODE_USERS)) {
            return Syncer::retrieveLocals();
        }

        if(!count($ids = ConsoleHelper::stringToArray($this->option('users')))) {
            throw new \InvalidArgumentException('No users passed');
        }

        return \Illuminate\Support\Facades\Auth::getProvider()->createModel()
            ->newQuery()
            ->whereIn('id', $ids)
            ->get();
    }
}