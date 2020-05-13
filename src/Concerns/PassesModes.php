<?php

namespace Slides\Connector\Auth\Concerns;

/**
 * Trait PassesModes
 *
 * @package Slides\Connector\Auth\Concerns
 */
trait PassesModes
{
    /**
     * The passed modes.
     *
     * @var array
     */
    private $modes;

    /**
     * Parse and retrieve the modes.
     *
     * @return array
     */
    protected function modes(): array
    {
        $modes = [
            'passwords' => $this->option('passwords'),
            'users' => $this->option('users'),
        ];

        return $this->modes = array_filter($modes);
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
        return array_key_exists($mode, $this->modes());
    }

    /**
     * Checks whether any mode is passed.
     *
     * @return bool
     */
    protected function hasModes(): bool
    {
        return count($this->modes()) > 0;
    }

    /**
     * Write modes to the output.
     *
     * @param array $modes
     *
     * @return void
     */
    protected function displayModes(array $modes = null)
    {
        $modes = $this->formatModes($modes ?? $this->modes());

        if($modes) {
            $this->output->block('Passed modes: ' . $modes, null, 'comment');
        }
    }

    /**
     * Format the modes to a string.
     *
     * @param array $modes
     *
     * @return string
     */
    protected function formatModes(array $modes): string
    {
        return implode(', ', array_keys($modes));
    }
}