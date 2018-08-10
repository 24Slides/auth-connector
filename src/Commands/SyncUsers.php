<?php

namespace Slides\Connector\Auth\Commands;

use Slides\Connector\Auth\AuthService;
use Slides\Connector\Auth\Sync\Syncable;
use Slides\Connector\Auth\Client as AuthClient;
use Slides\Connector\Auth\Sync\User as SyncUser;
use Illuminate\Support\Facades\Auth;
use Slides\Connector\Auth\Sync\Syncer;

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
    protected $signature = 'connector:sync-users
                            {--passwords= : Allow syncing passwords (can rewrite remotely and locally) }';

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
     * Whether remote and local passwords can be overwritten.
     *
     * @var bool
     */
    private $passwords;

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
        $syncer = new Syncer(
            $locals = Syncer::retrieveLocals(),
            $modes = $this->retrieveModes()
        );

        if(count($modes) > 0) {
            $this->output->block('Passed modes: ' . implode($modes, ', '), null, 'comment');
        }

        if($locals->isEmpty()) {
            $this->info('No local users found.');
        }

        if(!$this->confirm('There are ' . $locals->count() . ' local user(s) to sync. Continue?')) {
            return;
        }

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
     * Format the modes.
     *
     * @return array
     */
    protected function retrieveModes(): array
    {
        $modes = [
            'passwords' => $this->hasOption('passwords')
        ];

        return array_keys(array_filter($modes));
    }

    /**
     * Measure an execution time of the callback
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
}