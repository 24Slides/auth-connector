<?php

namespace Slides\Connector\Auth\Helpers;

/**
 * Class ConsoleHelper
 *
 * @package Slides\Connector\Auth\Helpers
 */
class ConsoleHelper
{
    /**
     * Convert a string like "field1:value1,field2:value" to the array
     *
     * Also supports one-dimensional array in the representation "value1,value2,value3"
     *
     * @param string|null $string
     * @param string $valueDelimiter
     * @param string $itemDelimiter
     *
     * @return array
     */
    public static function stringToArray(string $string = null, string $valueDelimiter = ':', string $itemDelimiter = ',')
    {
        if(is_null($string)) {
            return [];
        }

        $values = [];
        $items = preg_split('/' . preg_quote($itemDelimiter) . '/', $string, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($items as $index => $item) {
            $item = explode($valueDelimiter, $item);

            $key = array_get($item, 0);
            $value = array_get($item, 1);

            if(is_null($value)) {
                $value = $key;
                $key = $index;
            }

            $values[trim($key)] = trim($value);
        }

        return $values;
    }
}