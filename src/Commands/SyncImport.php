<?php

namespace Slides\Connector\Auth\Commands;

use Slides\Connector\Auth\Sync\Syncer;
use Slides\Connector\Auth\Concerns\PassesModes;

/**
 * Class SyncImport
 *
 * @package Slides\Connector\Auth\Commands
 */
class SyncImport extends \Illuminate\Console\Command
{
    use PassesModes;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connector:sync-import {filename}
                            { --k|key=    : Encryption key }
                            { --passwords : Allow syncing passwords (can rewrite remotely and locally) }
                            { --users=    : Sync the specific users }
                            { --no-modes  : Omit all modes }';

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

        $noModes = $this->option('no-modes');

        $this->flushListeners();

        $syncer = new Syncer(null, $noModes ? [] : $this->modes());
        $syncer->setOutputCallback(function(string $message) {
            $this->info('[Syncer] ' . $message);
        });


        $this->info('Importing the dump...');

        $syncer->import($this->argument('filename'), $key, $noModes ? false : !$this->hasModes());

        $this->displayModes($syncer->getModes());

        $changes = $syncer->getForeignersCount();

        if(!$this->confirm('Apply ' . $changes . ' changes?', true)) {
            return;
        }

        $duration = $this->measure(function() use ($syncer, $changes) {
            $this->info("Applying {$changes} changes...");

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

    /**
     * Flush models event listeners to speed-up the process.
     *
     * @return void
     */
    protected function flushListeners()
    {
        if(class_exists('\App\Http\Models\User')) {
            \App\Http\Models\User::flushEventListeners();
        }

        if(class_exists('App\Http\Models\CustomerProfile')) {
            \App\Http\Models\CustomerProfile::flushEventListeners();
        }

        if(class_exists('App\Modules\Billing\Models\Account::class')) {
            \App\Modules\Billing\Models\Account::flushEventListeners();
        }
    }
}