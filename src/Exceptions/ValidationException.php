<?php

namespace App\Services\Auth\Exceptions;

/**
 * Class ValidationException
 *
 * @package App\Services\Auth\Exceptions
 */
class ValidationException extends \Illuminate\Validation\ValidationException
{
    /**
     * The attribute names which should be renamed
     *
     * @var array
     */
    protected static $attributeMap = [
        'username' => 'email'
    ];

    /**
     * The list of attributes which should be hidden
     *
     * @var array
     */
    protected static $attributeHides = [
        'userId'
    ];

    public static function create(string $message)
    {
        $messages = static::createMessages($message);

        return static::withMessages($messages);
    }

    /**
     * Create messages
     *
     * @param string $message
     *
     * @return array
     */
    private static function createMessages(string $message): array
    {
        $messages = [];

        foreach (json_decode($message, true) as $property => $attributeMessages) {
            if(in_array($property, static::$attributeHides)) {
                continue;
            }

            // Rename an attribute according the mapping, if not listed, use the same
            $property = array_get(static::$attributeMap, $property, $property);

            $messages[$property] = static::formatMessages($attributeMessages);
        }

        return $messages;
    }

    /**
     * Format given messages
     *
     * @param array $messages
     *
     * @return array
     */
    private static function formatMessages(array $messages): array
    {
        foreach ($messages as $key => $message) {
            foreach (static::$attributeMap as $attribute => $replacement) {
                $messages[$key] = str_replace($attribute, $replacement, $message);
            }
        }

        return $messages;
    }
}