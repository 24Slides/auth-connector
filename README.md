# Integration with Authentication Service

[![Latest Stable Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Total Downloads][ico-downloads]][link-downloads]

Simplifies integration with third-party services including ready-to-use solutions like HTTP Client, Auth Guard, 
synchronization, encryption etc.

## Getting Started

### Installation

- Define the following environment variables, obtain them from the server admin:

```
JWT_SECRET=

SERVICE_AUTH_ENALBED=true
SERVICE_AUTH_URL=
SERVICE_AUTH_PUBLIC=
SERVICE_AUTH_SECRET=
```
- Install a dependency via Composer: `composer require 24slides/auth-connector`
- Add a package provider to `config/app.php`:
```php
'providers' => [
    ...
    Slides\Connector\Auth\ServiceProvider::class,
```

> The provider must be defined after `AuthServiceProvider`.

- Define auth guards at `config/auth.php`:

```php
'guards' => [
    ...
    
    'authService' => [
        'driver' => 'authServiceToken',
        'provider' => 'users',
    ],
    
    'fallback' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],
```

> Fallback is your default authentication guard which activates when remote service is disabled

- Replace default guard to `authService`:

```php
'defaults' => [
    'guard' => 'authService',
    'passwords' => 'users',
],
```

> If you want to enable IDE features like hints, you need to install [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper).

- Publish config and migrations:

```php
php artisan vendor:publish --provider Slides\Connector\Auth\ServiceProvider
```

- Run the published migration: `php artisan migrate`

> The migration adds `remote_id` column to your User model which is supposed to be an identificator of synced users.

- Disable encryption for the auth cookie which identifies a user:

**`app/Http/Middleware/EncryptCookies:`**
```php
/**
 * The names of the cookies that should not be encrypted.
 *
 * @var array
 */
protected $except = [
    'authKey'
];
```

- Disable verifying CSRF token for incoming webhooks:

**`app/Http/Middleware/VerifyCsrfToken:`**
```php
/**
 * The URIs that should be excluded from CSRF verification.
 *
 * @var array
 */
protected $except = [
    'connector/webhook/*'
];
```

### Syncing users

To allow syncing users, implement the `Slides\Connector\Auth\Sync\Syncable` interface on your `User` model.

There is a trait helper `Slides\Connector\Auth\Concerns\UserHelper` which covers almost all the methods, except `retrieveCountry`, 
which requires 2-digit country code (ISO 3166-1 alpha-2). If you have custom attributes just override methods from there.

### Authentication handlers

Handlers implement authentication operation and wrapped into database transactions. It's just a layer to keep logic in one place.

To generate a handler, you should use the following command:

```bash
php artisan make:auth-handlers
```

It'll create you a file `AuthHandlers.php` under the namespace `App\Services\Auth`.

Once file has created, you should add to the `boot` in the `app/Services/AuthServiceProvider.php`:

```php
$this->app['authService']->loadHandlers($this->app[\App\Services\Auth\AuthHandlers::class]);
```

You can find an example of handlers implementations [here](examples/auth-handlers.md).

#### Fallbacks

Connector provides the possibility to disable the remote service by setting `SERVICE_AUTH_ENALBED=false`.
It means authentication operations like login, logout, password reset will be processed only locally.

### Implementing auth logic

To make authentication service work, you need to replace your own logic.
The following pointsshould be replaced:

- Registration
- Login
- Password reset

#### Registration

- In a case if you follow default Laravel implementation of user registration, you should replace the trait 
`Illuminate\Foundation\Auth\RegistersUsers` with `Slides\Connector\Auth\Concerns\RegistersUsers` 
at the controller `App\Http\Controllers\Auth\RegistrationController`.`

> If you have customized registration logic, you can override trait's methods.

- Delete the `create()` method since it implements by `RegistersUsers` trait.

#### Login

In a case if you follow default Laravel implementation of user registration, you should replace the trait 
`Illuminate\Foundation\Auth\AuthenticatesUsers` with `Slides\Connector\Auth\Concerns\AuthenticatesUsers` 
at the controller `App\Http\Controllers\Auth\LoginController`.

> If you have customized login logic, you can override trait's methods.

#### Password reset

- In a case if you follow default Laravel implementation of user registration, you should replace the traits on the following files:
  - *`App\Http\Controllers\Auth\ForgotPasswordController`*: 
  replace `Illuminate\Foundation\Auth\SendsPasswordResetEmails` with `Slides\Connector\Auth\Concerns\SendsPasswordResetEmails`
  - *`App\Http\Controllers\Auth\ResetPasswordController`*: 
  replace `Illuminate\Foundation\Auth\ResetsPasswords` with `Slides\Connector\Auth\Concerns\ResetsPasswords`

> If you have customized password resetting logic, you can override trait's methods.

- If your `User` model doesn't implement `UserHelpers` trait, define the following method there:

```php
/**
 * Send the password reset notification.
 *
 * @param string $token
 *
 * @return void
 */
public function sendPasswordResetNotification(string $token)
{
    $this->notify(new \Slides\Connector\Auth\Notifications\ResetPasswordNotification($token));
}
```

- Override the reset form route by adding to `routes/web.php`: 

```php
Route::get('password/reset/{token}/{email}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
```

[ico-version]: https://poser.pugx.org/24slides/auth-connector/v/stable?format=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/24Slides/auth-connector.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/24slides/auth-connector.svg?style=flat-square
[ico-code-coverage]: https://img.shields.io/scrutinizer/coverage/g/24slides/auth-connector.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/24slides/auth-connector.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/24slides/auth-connector
[link-travis]: https://travis-ci.org/24Slides/auth-connector
[link-scrutinizer]: https://scrutinizer-ci.com/g/24slides/auth-connector/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/24slides/auth-connector
[link-code-coverage]: https://scrutinizer-ci.com/g/24Slides/auth-connector
[link-downloads]: https://packagist.org/packages/24slides/auth-connector