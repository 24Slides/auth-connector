<?php

namespace Slides\Connector\Auth\Commands;

use Slides\Connector\Auth\Sync\Syncer;

/**
 * Class SyncImport
 *
 * @package Slides\Connector\Auth\Commands
 */
class SyncImport extends \Illuminate\Console\Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connector:sync-import {filename}
                            { --k|key= : Encryption key }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize remote difference to apply latest changes locally';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if(!$key = $this->option('key')) {
            throw new \InvalidArgumentException('Encryption key must be passed.');
        }

        $syncer = new Syncer();

        $duration = $this->measure(function() use ($syncer, $key) {
            $this->info('Importing the dump...');

            $syncer->import($this->argument('filename'), $key);

            $this->info("Applying {$syncer->getForeignersCount()} changes...");

            $syncer->apply();
        });

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
}