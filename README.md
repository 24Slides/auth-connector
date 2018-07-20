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

- Install a dependency via Composer: `composer require 24slides/auth-connector`
- Define the following environment variables, obtain **public** and **secret** keys before:

```
SERVICE_AUTH_URL=https://auth.24slides.com/v1
SERVICE_AUTH_PUBLIC=
SERVICE_AUTH_SECRET=
```

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

You can find examples of handler implementations [here](examples).

#### Fallbacks

Connector provides the possibility to disable the remote service. 
It means authentication operations like login, logout, password reset should be processed locally.

To reach that, you need to implement the following fallback handlers on your handler class:

```php
fallbackLogin($guard, string $email, string $password, bool $remember = false): bool
fallbackLogout($guard): void
fallbackForgot($guard, string $email): bool
fallbackValidateReset($guard, string $email): string|false
fallbackResetPassword($guard, string $token, string $email, string $password, string $confirmation): array|false
```

### Testing

