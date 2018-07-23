# Integration with Authentication Service

Simplifies integration with third-party services including ready-to-use solutions like HTTP Client, Auth Guard, 
synchronization, encryption etc.

## Getting Started

### Private repositories as dependencies

To allow installing private repositories through the composer, the easiest way is to obtain GitHub Oauth Access Token:

- Go to https://github.com/settings/tokens, click "Generate new token", set "Composer" as a description, 
tick `repo` and click "Generate".
- Create a file `auth.json` in the project root, insert the following code:
```
{
  "github-oauth": {
    "github.com": "..."
  }
}
```
- Paste your generated token instead of dots.
- Add `auth.json` to `.gitignore`
- Paste the following code to `composer.json`
```
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:24Slides/24slides.git"
    }
],
```

### Installation

- Define the following environment variables, obtain them from the server admin:

```
SERVICE_AUTH_URL=https://auth.24slides.com/v1
SERVICE_AUTH_PUBLIC=
SERVICE_AUTH_SECRET=

JWT_SECRET=
```
- Install a dependency via Composer: `composer require 24slides/auth-connector`
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

### Syncing users

To allow syncing users, implement the `Slides\Connector\Auth\Sync\Syncable` interface on your `User` model.

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

You can find examples of handler implementations [here](examples/auth-handlers.md).

#### Fallbacks

Connector provides the possibility to disable the remote service. 
It means authentication operations like login, logout, password reset should be processed locally.

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

