<?php

namespace Slides\Connector\Auth\Commands;

/**
 * Class MakeAuthHandlers
 *
 * @package Slides\Connector\Auth\Commands
 */
class MakeAuthHandlers extends \Illuminate\Console\GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:auth-handlers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a skeleton of the authentication handlers';

    /**
     * @inheritdoc
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/auth-handlers.stub';
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return 'Services/Auth/AuthHandlers';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
}