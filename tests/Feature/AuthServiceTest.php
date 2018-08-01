<?php

namespace Slides\Connector\Auth\Tests\Feature;

use Slides\Connector\Auth\AuthService;
use GuzzleHttp\Psr7\Response;

class AuthServiceTest extends \Slides\Connector\Auth\Tests\TestCase
{
    public function testDisabled()
    {
        $service = $this->mockService();
        $config = $this->app['config'];

        $config->set('connector.auth.enabled', true);

        static::assertFalse($service->disabled());

        $config->set('connector.auth.enabled', false);

        static::assertTrue($service->disabled());
    }

    /**
     * @covers \Slides\Connector\Auth\AuthService::login()
     * @covers \Slides\Connector\Auth\Client::login()
     */
    public function testLoginSuccess()
    {
        $service = $this->mockService([
            new Response(200, [], $response = $this->stub(__DIR__ . '/responses/login-success.json'))
        ]);

        $token = $service->login('test@test.com', 'secret', false);
        
        $expected = array_get(json_decode($response, true), 'token');

        static::assertSame($expected, $token);
    }

    public function testLoginInvalid()
    {
        $service = $this->mockService([
            new Response(200, [], $this->stub(__DIR__ . '/responses/login-invalid.json'))
        ]);

        $token = $service->login('test@test.com', 'secret', false);

        static::assertFalse($token);
    }

    /**
     * @expectedException \Slides\Connector\Auth\Exceptions\ValidationException
     */
    public function testLoginValidationError()
    {
        $service = $this->mockService([
            new Response(422, [], $this->stub(__DIR__ . '/responses/login-validation-error.json'))
        ]);

        $service->login('test@testcom', 'secret', false);
    }

    /**
     * @covers \Slides\Connector\Auth\AuthService::unsafeLogin()
     * @covers \Slides\Connector\Auth\Client::unsafeLogin()
     */
    public function testUnsafeLoginSuccess()
    {
        $service = $this->mockService([
            new Response(200, [], $response = $this->stub(__DIR__ . '/responses/unsafe-login-success.json'))
        ]);

        $token = $service->unsafeLogin('test@test.com', false);

        $expected = array_get(json_decode($response, true), 'token');

        static::assertSame($expected, $token);
    }

    public function testUnsafeLoginInvalid()
    {
        $service = $this->mockService([
            new Response(200, [], $this->stub(__DIR__ . '/responses/unsafe-login-invalid.json'))
        ]);

        $token = $service->unsafeLogin('test@test.com', false);

        static::assertFalse($token);
    }

    /**
     * @expectedException \Slides\Connector\Auth\Exceptions\ValidationException
     */
    public function testUnsafeLoginValidationError()
    {
        $service = $this->mockService([
            new Response(422, [], $this->stub(__DIR__ . '/responses/unsafe-login-validation-error.json'))
        ]);

        $service->login('test@testcom', false);
    }

    /**
     * @covers \Slides\Connector\Auth\AuthService::register()
     * @covers \Slides\Connector\Auth\Client::register()
     */
    public function testRegisterSuccess()
    {
        $service = $this->mockService([
            new Response(200, [], $expected = $this->stub(__DIR__ . '/responses/register-success.json'))
        ]);

        $response = $service->register(1, 'Test', 'test@test.com', 'secret');

        static::assertSame(json_decode($expected, true), $response);
    }

    /**
     * @expectedException \Slides\Connector\Auth\Exceptions\ValidationException
     */
    public function testRegisterValidationError()
    {
        $service = $this->mockService([
            new Response(422, [], $this->stub(__DIR__ . '/responses/register-validation-error.json'))
        ]);

        $service->register(1, 'Test', 'test@test.com', 'secret');
    }

    /**
     * @covers \Slides\Connector\Auth\AuthService::forgot()
     * @covers \Slides\Connector\Auth\Client::forgot()
     */
    public function testForgotSuccess()
    {
        $service = $this->mockService([
            new Response(200, [], $this->stub(__DIR__ . '/responses/forgot-success.json'))
        ]);

        static::assertTrue($service->forgot('test@test.com'));
    }

    public function testForgotError()
    {
        $service = $this->mockService([
            new Response(200, [], $this->stub(__DIR__ . '/responses/forgot-error.json'))
        ]);

        static::assertFalse($service->forgot('test@test.com'));
    }

    /**
     * @covers \Slides\Connector\Auth\AuthService::validatePasswordResetToken()
     * @covers \Slides\Connector\Auth\Client::validateReset()
     */
    public function testValidatePasswordResetTokenSuccess()
    {
        $service = $this->mockService([
            new Response(200, [], $this->stub(__DIR__ . '/responses/validate-reset-success.json'))
        ]);

        $response = $service->validatePasswordResetToken(str_random(), 'test@test.com');

        static::assertSame($response, 'test@test.com');
    }

    public function testValidatePasswordResetTokenError()
    {
        $service = $this->mockService([
            new Response(200, [], $this->stub(__DIR__ . '/responses/validate-reset-error.json'))
        ]);

        $response = $service->validatePasswordResetToken(str_random(), 'test@test.com');

        static::assertFalse($response, 'test@test.com');
    }

    /**
     * @covers \Slides\Connector\Auth\AuthService::resetPassword()
     * @covers \Slides\Connector\Auth\Client::reset()
     */
    public function testResetPasswordSuccess()
    {
        $service = $this->mockService([
            new Response(200, [], $expected = $this->stub(__DIR__ . '/responses/reset-success.json'))
        ]);

        $response = $service->resetPassword(str_random(), 'test@test.com', 'secret', 'secret');

        static::assertSame(json_decode($expected, true), $response);
    }

    public function testResetPasswordError()
    {
        $service = $this->mockService([
            new Response(200, [], $this->stub(__DIR__ . '/responses/reset-error.json'))
        ]);

        $response = $service->resetPassword(str_random(), 'test@test.com', 'secret', 'secret');

        static::assertFalse($response);
    }

    /**
     * @covers \Slides\Connector\Auth\AuthService::update()
     * @covers \Slides\Connector\Auth\Client::update()
     */
    public function testUpdateSuccess()
    {
        $service = $this->mockService([
            new Response(200, [], $expected = $this->stub(__DIR__ . '/responses/update-success.json'))
        ]);

        $response = $service->update(1, 'Test', 'test@test.com', 'secret');

        static::assertSame(json_decode($expected, true), $response);
    }

    public function testUpdateError()
    {
        $service = $this->mockService([
            new Response(200, [], $this->stub(__DIR__ . '/responses/update-error.json'))
        ]);

        $response = $service->update(0, 'Test', 'test@test.com', 'secret');

        static::assertFalse($response);
    }

    public function testHandle()
    {
        static::assertTrue(
            $this->mockService()->handle('test')
        );
    }

    public function testHandleWithParameters()
    {
        static::assertTrue(
            $this->mockService()->handle('testParams', ['string' => 'Hello world!', 'array' => []])
        );
    }

    /**
     * @expectedException \ArgumentCountError
     */
    public function testHandleWithInvalidParameters()
    {
        static::assertTrue(
            $this->mockService()->handle('testParams')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHandleInvalid()
    {
        $this->mockService()->handle('unknown');
    }

    public function testHandleFailed()
    {
        $output = $this->mockService()->handle('testFailed', [], function($e) {
            static::assertInstanceOf(\Exception::class, $e);

            return false;
        });

        static::assertFalse($output);
    }

    public function testHandleFallback()
    {
        static::assertTrue(
            $this->mockService()->handleFallback('test')
        );
    }

    public function testHandleFallbackFailed()
    {
        $output = $this->mockService()->handleFallback('testFailed', [], function ($e) {
            static::assertInstanceOf(\Exception::class, $e);

            return false;
        });

        static::assertFalse($output);
    }

    /**
     * Mock the authentication service
     *
     * @param array $responses
     *
     * @return AuthService
     */
    protected function mockService(array $responses = [])
    {
        require_once __DIR__ . '/classes/AuthHandlers.php';

        $service = new AuthService(
            $this->mockClient($responses)
        );

        $service->setGuard($this->mockGuard($service));
        $service->loadHandlers(new \AuthHandlers());

        return $service;
    }

    /**
     * Mock the HTTP Client
     *
     * @param array $responses
     *
     * @return \Slides\Connector\Auth\Client
     */
    protected function mockClient(array $responses)
    {
        $client = new \GuzzleHttp\Client([
            'handler' => \GuzzleHttp\HandlerStack::create(
                new \GuzzleHttp\Handler\MockHandler($responses)
            ),
            'http_errors' => false
        ]);

        return new \Slides\Connector\Auth\Client($client);
    }

    /**
     * Mock the Token Guard.
     *
     * @param AuthService $authService
     *
     * @return \Slides\Connector\Auth\TokenGuard
     */
    protected function mockGuard(AuthService $authService)
    {
        $request = new \Illuminate\Http\Request();

        return new \Slides\Connector\Auth\TokenGuard(
            $this->mockUserProvider(),
            $request,
            $authService,
            $authService->getClient()
        );
    }

    /**
     * Mock the user provider.
     *
     * @return \Illuminate\Contracts\Auth\UserProvider
     */
    protected function mockUserProvider()
    {
        /** @var \Mockery\MockInterface|\Illuminate\Auth\DatabaseUserProvider $provider */
        $provider = \Mockery::mock(\Illuminate\Auth\DatabaseUserProvider::class, [
            $this->app['db.connection'],
            $this->app['hash'],
            'users'
        ]);

        $provider->makePartial();

        return $provider;
    }
}