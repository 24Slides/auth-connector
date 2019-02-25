<?php

namespace Slides\Connector\Auth\Tests\Unit\Helpers;

use Slides\Connector\Auth\Helpers\ArrayHelper;

/**
 * Class ArrayHelperTest
 *
 * @package App\Tests\Unit\Helpers
 */
class ArrayHelperTest extends \Slides\Connector\Auth\Tests\TestCase
{
    public function testReplaceValuesByMatchingKeys()
    {
        static::assertEquals([], ArrayHelper::replaceValuesByMatchingKeys([], [], '...'));

        static::assertEquals(
            $array = ['password' => 'qwerty123'],
            ArrayHelper::replaceValuesByMatchingKeys($array, [], '...')
        );

        static::assertEquals(
            ['Password' => '...'],
            ArrayHelper::replaceValuesByMatchingKeys(['Password' => 'qwerty123'], ['password'], '...')
        );

        static::assertEquals(
            ['password' => '...'],
            ArrayHelper::replaceValuesByMatchingKeys(['password' => 'qwerty123'], ['password'], function($key, $value) {
                return '...';
            })
        );

        static::assertEquals(
            ['user' => ['password' => '...'], 'password' => ['foo', 'bar']],
            ArrayHelper::replaceValuesByMatchingKeys(
                ['user' => ['password' => 'qwerty123'], 'password' => ['foo', 'bar']],
                ['password'], '...'
            )
        );
    }
};