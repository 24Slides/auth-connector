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

### Development



### Testing

