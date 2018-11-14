<?php

/**
 * Class AuthHandlers
 *
 * @package Slides\Connector\Auth\Tests\Feature
 */
class AuthHandlers
{
    /**
     * Method for testing the access.
     *
     * @return bool
     */
    public function test()
    {
        return true;
    }

    /**
     * Method for testing the parameters passing.
     *
     * @param string $string
     * @param array $array
     *
     * @return bool
     */
    public function testParams(string $string, array $array)
    {
        return true;
    }

    /**
     * Method for testing the exception throwing.
     *
     * @throws Exception
     */
    public function testFailed()
    {
        throw new Exception();
    }

    /**
     * Method for testing the access of the fallback handler.
     *
     * @return bool
     */
    public function fallbackTest()
    {
        return true;
    }

    /**
     * Method for testing the exception in fallback throwing.
     *
     * @throws Exception
     */
    public function fallbackTestFailed()
    {
        throw new Exception();
    }
}