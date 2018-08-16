<?php

namespace Slides\Connector\Auth\Commands;

use Slides\Connector\Auth\AuthService;
use Slides\Connector\Auth\Client as AuthClient;
use Slides\Connector\Auth\Sync\Syncer;
use Slides\Connector\Auth\Helpers\ConsoleHelper;

/**
 * Class ImportUsers
 *
 * @package Slides\Connector\Auth\Commands
 */
class SyncExport extends \Illuminate\Console\Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connector:sync-export
                            {--path=  : Allow syncing passwords (can rewrite remotely and locally) }
                            {--users= : Import the specific users }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export local users to sync users remotely (can be imported only remotely)';

    /**
     * @var AuthClient
     */
    protected $authClient;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * The list of enabled modes.
     *
     * @var string[]
     */
    protected $modes;

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
        $syncer = new Syncer($locals = $this->syncingUsers());

        if($locals->isEmpty()) {
            $this->info('No local users found.');
        }

        if(!$this->confirm('There are ' . $locals->count() . ' local user(s) to export. Continue?')) {
            return;
        }

        $syncer->setOutputCallback(function(string $message) {
            $this->info('[Syncer] ' . $message);
        });

        $duration = $this->measure(function() use ($syncer) {
            $syncer->export($this->filePath());
        });

        $this->info('Dump has been saved as ' . $this->filePath());
        $this->info('Decryption key: ' . $syncer->getEncryptionKey());
        $this->info("Finished in {$duration}s.");
    }

    /**
     * Format the modes.
     *
     * @return array
     */
    protected function retrieveModes(): array
    {
        $modes = [
            'passwords' => $this->option('passwords'),
            'users' => $this->hasOption('users'),
        ];

        return array_keys(array_filter($modes));
    }

    /**
     * Checks whether user has a mode.
     *
     * @param string $mode
     *
     * @return bool
     */
    protected function hasMode(string $mode): bool
    {
        return in_array($mode, $this->modes);
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
        if(!count($ids = ConsoleHelper::stringToArray($this->option('users')))) {
            return Syncer::retrieveLocals();
        }

        return \Illuminate\Support\Facades\Auth::getProvider()->createModel()
            ->newQuery()
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * Retrieve a file path.
     *
     * @return string
     */
    private function filePath()
    {
        if(!$path = $this->option('path')) {
            $path = storage_path('app');
        }

        $datetime = (new \Carbon\Carbon())->format('Y_m_d_His');

        return str_finish($path, '/') . 'sync_export_' . $datetime . '.gz';
    }
}