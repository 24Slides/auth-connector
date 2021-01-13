<?php

namespace Slides\Connector\Auth\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Slides\Connector\Auth\Helpers\ConsoleHelper;
use Slides\Connector\Auth\AuthService;

/**
 * Class ManageUsers
 *
 * @package Slides\Connector\Auth\Commands
 */
class ManageUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manage:users {action} {ids}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage user(s): delete, restore';

    /**
     * The actions.
     *
     * @var array
     */
    protected $actions = [
        'delete',
        'restore'
    ];

    /**
     * User IDs.
     *
     * @var array
     */
    protected $ids;

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
        $this->ids = ConsoleHelper::stringToArray($ids = $this->argument('ids'));

        $this->line('Passed user ids: <info>' . $ids . '</info>');

        $this->runActionHandler($this->argument('action'));
    }

    /**
     * Run an action handler.
     *
     * @param string $action
     *
     * @return mixed
     */
    protected function runActionHandler(string $action)
    {
        if(!in_array($action, $this->actions)) {
            throw new \InvalidArgumentException("Unknown action `{$action}` passed");
        }

        $actionHandler = 'handle' . Str::studly($action);

        if(!method_exists($this, $actionHandler)) {
            throw new \InvalidArgumentException("Cannot find action handler `{$actionHandler}`");
        }

        $this->line("Handling the <info>{$action}</info> action");

        return $this->{$actionHandler}();
    }

    /**
     * Delete users.
     *
     * @return void
     */
    protected function handleDelete()
    {
        $users = $this->retrieveUsers(['id' => $this->ids]);

        if ($users->isEmpty()) {
            $this->info('No users found.');
            return;
        } else {
            if (!$this->confirm('Do you want to delete ' . $users->count() . ' user(s)?')) {
                return;
            }
        }

        foreach ($users as $user) {
            try {
                $this->authService->handle('delete', ['user' => $user]);

                $this->info('User #' . $user->id . ' (' . $user->email . ') successfully deleted.');
            } catch (\Exception $e) {
                $this->error('User #' . $user->id . ' (' . $user->email . ') cannot be deleted. ' . $e->getMessage());
            }
        }
    }

    /**
     * Restore users.
     *
     * @return void
     */
    protected function handleRestore()
    {
        $users = $this->retrieveUsers(['id' => $this->ids], true);

        if($users->isEmpty()) {
            $this->info('No users found.');
            return;
        }
        else {
            if(!$this->confirm('Do you want to restore ' . $users->count() . ' user(s)?')) {
                return;
            }
        }

        foreach ($users as $user) {
            try {
                $this->authService->handle('restore', ['user' => $user]);

                $this->info('User #' . $user->id . ' (' . $user->email . ') successfully restored.');
            }
            catch(\Exception $e) {
                $this->error('User #' . $user->id . ' (' . $user->email . ') cannot be restored. ' . $e->getMessage());
            }
        }
    }

    /**
     * Retrieve users.
     *
     * @param array $conditions
     * @param bool $onlyTrashed
     *
     * @return \Illuminate\Support\Collection
     */
    protected function retrieveUsers(array $conditions, bool $onlyTrashed = false)
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = \Auth::getProvider()->createModel()->newQueryWithoutScopes();

        foreach ($conditions as $column => $value) {
            $query->whereIn($column, $value);
        }

        if ($onlyTrashed) {
            $query->whereNotNull('deleted_at');
        }

        return $query->get();
    }
}
