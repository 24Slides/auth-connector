<?php

namespace Slides\Connector\Auth\Helpers;

/**
 * Class ArrayHelper
 *
 * @package Slides\Connector\Auth\Helpers
 */
class ArrayHelper
{
    /**
     * Replace values that matches with the given keys.
     *
     * @param array $input Replacing array
     * @param array $keys
     * @param \Closure|string $replacement Handling callback
     *
     * @return array
     */
    public static function replaceValuesByMatchingKeys(array $input, array $keys, $replacement)
    {
        $keys = array_map('strtolower', $keys);

        foreach ($input as $key => $value) {
            if(is_array($value)) {
                $input[$key] = static::replaceValuesByMatchingKeys($value, $keys, $replacement);
            }
            elseif(is_string($key) && in_array(strtolower($key), $keys)) {
                $input[$key] = $replacement instanceof \Closure
                    ? $replacement($key, $value)
                    : $replacement;
            }
        }

        return $input;
    }
}