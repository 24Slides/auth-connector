<?php

namespace Slides\Connector\Auth\Commands;

use Slides\Connector\Auth\Sync\Syncer;
use Slides\Connector\Auth\Helpers\ConsoleHelper;

/**
 * Class SyncExport
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

        if(!$this->confirm('There are ' . $locals->count() . ' local user(s) to export. Continue?', $this->option('no-interaction'))) {
            return;
        }

        $syncer->setOutputCallback(function(string $message) {
            $this->info('[Syncer] ' . $message);
        });

        $duration = $this->measure(function() use ($syncer) {
            $syncer->export($this->filePath());
        });

        $this->info('Dump has been saved to ' . $this->filePath());
        $this->info('Encryption key: ' . $syncer->getEncryptionKey());

        $filename = basename($this->filePath());

        $this->output->note(
            'This encryption key is unique for each dump and supposed to be used safely.'
            . PHP_EOL . 'It\'s bound to this service and cannot be used on other tenants.'
        );

        $this->output->block('To sync the dump, run the following command on Authentication Service:');
        $this->output->block("php artisan sync:import-dump {$filename} --key \"{$syncer->getEncryptionKey()}\"", null, 'fg=cyan;bg=default');

        $this->info("Finished in {$duration}s.");
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