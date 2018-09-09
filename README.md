# PSR-7 and PSR-15 Basic Auth Middleware

This middleware implements [HTTP Basic Authentication](https://en.wikipedia.org/wiki/Basic_access_authentication). It was originally developed for Slim but can be used with all frameworks using PSR-7 or PSR-15 style middlewares. It has been tested  with [Slim Framework](http://www.slimframework.com/) and [Zend Expressive](https://zendframework.github.io/zend-expressive/).


[![Latest Version](https://img.shields.io/packagist/v/tuupola/slim-basic-auth.svg?style=flat-square)](https://packagist.org/packages/tuupola/slim-basic-auth)
[![Packagist](https://img.shields.io/packagist/dm/tuupola/slim-basic-auth.svg)](https://packagist.org/packages/tuupola/slim-basic-auth)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://img.shields.io/travis/tuupola/slim-basic-auth/3.x.svg?style=flat-square)](https://travis-ci.org/tuupola/slim-basic-auth)
[![Coverage](https://img.shields.io/codecov/c/github/tuupola/slim-basic-auth/3.x.svg?style=flat-square)](https://codecov.io/gh/tuupola/slim-basic-auth/branch/3.x)

Heads up! You are reading documentation for [3.x branch](https://github.com/tuupola/slim-basic-auth/tree/3.x) which is PHP 7.1 and up only. If you are using older version of PHP see the [2.x branch](https://github.com/tuupola/slim-basic-auth/tree/2.x). These two branches are not backwards compatible, see [UPGRADING](https://github.com/tuupola/slim-basic-auth/blob/3.x/UPGRADING.md) for instructions how to upgrade.

## Install

Install latest version using [composer](https://getcomposer.org/).

```
$ composer require tuupola/slim-basic-auth
```

## Usage

Configuration options are passed as an array. Only mandatory parameter is  `users`. This is an array where you pass one or more `"username" => "password"` combinations. Username is the key and password is the value.

```php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

Same with Zend Expressive.

```php
$app = Zend\Expressive\AppFactory::create();

$app->pipe(new Tuupola\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => "t00r",
        "user" => "passw0rd"
    ]
]));
```

Rest of the examples assume you are using Slim Framework.

Cleartext passwords are only good for quick testing. You probably want to use hashed passwords. Hashed password can be generated with `htpasswd` command line tool or [password_hash()](http://php.net/manual/en/function.password-hash.php) PHP function

```
$ htpasswd -nbBC 10 root t00r
root:$2y$10$1lwCIlqktFZwEBIppL4ak.I1AHxjoKy9stLnbedwVMrt92aGz82.O
$ htpasswd -nbBC 10 somebody passw0rd
somebody:$2y$10$6/vGXuMUoRlJUeDN.bUWduge4GhQbgPkm6pfyGxwgEWT0vEkHKBUW
```

```php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => '$2y$10$1lwCIlqktFZwEBIppL4ak.I1AHxjoKy9stLnbedwVMrt92aGz82.O',
        "somebody" => '$2y$10$6/vGXuMUoRlJUeDN.bUWduge4GhQbgPkm6pfyGxwgEWT0vEkHKBUW'
    ]
]));
```

Even if you are using hashed passwords it is not the best idea to store credentials in the code. Instead you could store them in environment or external file which is not committed to GitHub.

```php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "users" => [
        "admin" => getenv("ADMIN_PASSWORD")
    ]
]));
```

## Optional parameters
### Path

The optional `path` parameter allows you to specify the protected part of your website. It can be either a string or an array. You do not need to specify each URL. Instead think of `path` setting as a folder. In the example below everything starting with `/api` will be authenticated.

``` php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => "/api", /* or ["/admin", "/api"] */
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

### Ignore

With optional `ignore` parameter you can make exceptions to `path` parameter. In the example below everything starting with `/api` and `/admin`  will be authenticated with the exception of `/api/token` and `/admin/ping` which will not be authenticated.

``` php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => ["/api", "/admin"],
    "ignore" => ["/api/token", "/admin/ping"],
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

### Before

Before function is called only when authentication succeeds but before the next incoming middleware is called. You can use this to alter the request before passing it to the next incoming middleware in the stack. If it returns anything else than `\Psr\Http\Message\RequestInterface` the return value will be ignored.

```php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ],
    "before" => function ($request, $arguments) {
        return $request->withAttribute("user", $arguments["user"]);
    }
]));
```

### After

After function is called only when authentication succeeds and after the incoming middleware stack has been called. You can use this to alter the response before passing it next outgoing middleware in the stack. If it returns anything else than `\Psr\Http\Message\ResponseInterface` the return value will be ignored.

```php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ],
    "after" => function ($request, $response, $arguments) {
        return $response->withHeader("X-Brawndo", "plants crave");
    }
]));
```

## Security

Basic authentication transmits credentials in clear text. For this reason HTTPS should always be used together with basic authentication. If the middleware detects insecure usage over HTTP it will throw a `RuntimeException` with the following message: `Insecure use of middleware over HTTP denied by configuration`.

By default, localhost is allowed to use HTTP. The security behavior of `HttpBasicAuthentication` can also be configured to allow:

- [a whitelist of domains to connect insecurely](#how-to-configure-a-whitelist)
- [forwarding of an HTTPS connection to HTTP](#allow-https-termination-and-forwarding)
- [all unencrypted traffic](#allow-all-unencrypted-traffic)

### How to configure a whitelist:
You can list hosts to allow access insecurely.  For example, to allow HTTP traffic to your development host `dev.example.com`, add the hostname to the `relaxed` config key.

``` php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "secure" => true,
    "relaxed" => ["localhost", "dev.example.com"],
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```
### Allow HTTPS termination and forwarding
If public traffic terminates SSL on a load balancer or proxy and forwards to the application host insecurely, `HttpBasicAuthentication` can inspect request headers to ensure that the original client request was initiated securely.  To enable, add the string `headers` to the `relaxed` config key.

```php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "secure" => true,
    "relaxed" => ["localhost", "headers"],
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

### Allow all unencrypted traffic
To allow insecure usage by any host, you must enable it manually by setting `secure` to `false`. This is generally a bad idea. Use only if you know what you are doing.

``` php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "secure" => false,
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```
## Custom authentication methods

Sometimes passing users in an array is not enough. To authenticate against custom datasource you can pass a callable as `authenticator` parameter. This can be either a class which implements AuthenticatorInterface or anonymous function. Callable receives an array containing `user` and `password` as argument. In both cases authenticator must return either `true` or `false`.

If you are creating an Enterprise&trade; software which randomly lets people log in you could use the following.


```php
use Tuupola\Middleware\HttpBasicAuthentication\AuthenticatorInterface;
use Tuupola\Middleware\HttpBasicAuthentication;

class RandomAuthenticator implements AuthenticatorInterface {
    public function __invoke(array $arguments) {
        return (bool)rand(0,1);
    }
}

$app = new Slim\App;

$app->add(new HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "authenticator" => new RandomAuthenticator
]));
```

Same thing can also be accomplished with anonymous function.

```php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "authenticator" => function ($arguments) {
        return (bool)rand(0,1);
    }
]));
```

## Setting response body when authentication fails

By default plugin returns an empty response body with 401 response. You can return custom body using by providing an error handler. This is useful for example when you need additional information why authentication failed.

```php
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => "/api",
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ],
    "error" => function ($response, $arguments) {
        $data = [];
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response->write(json_encode($data, JSON_UNESCAPED_SLASHES));
    }
]));
```

## Usage with PDO

For those in hurry there is a ready made PDO authenticator. It covers most of the use cases. You probably end up implementing your own though.

```php
use Tuupola\Middleware\HttpBasicAuthentication\PdoAuthenticator;

$pdo = new PDO("sqlite:/tmp/users.sqlite");
$app = new Slim\App;

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "authenticator" => new PdoAuthenticator([
        "pdo" => $pdo
    ])
]));
```

For better explanation see [Basic Authentication from Database](http://www.appelsiini.net/2014/slim-database-basic-authentication) blog post.

## Usage with FastCGI

By default Apache [does not pass credentials](https://bugs.php.net/bug.php?id=35752) to FastCGI process. If you are using mod_fcgi you can configure authorization headers with:

```
FastCgiExternalServer /usr/lib/cgi-bin/php5-fcgi -host 127.0.0.1:9000 -pass-header Authorization
```

## Testing

You can run tests either manually or automatically on every code change. Automatic tests require [entr](http://entrproject.org/) to work.

``` bash
$ make test
```

``` bash
$ brew install entr
$ make watch
```

## Contributing

Please see [CONTRIBUTING](https://github.com/tuupola/slim-basic-auth/blob/3.x/CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email tuupola@appelsiini.net instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](https://github.com/tuupola/slim-basic-auth/blob/3.x/LICENSE.md) for more information.


