# PSR-7 Basic Auth Middleware

This middleware implements [HTTP Basic Authentication](https://en.wikipedia.org/wiki/Basic_access_authentication). It was originally developed for Slim but can be used with all frameworks using PSR-7 style middlewares. It has been tested  with [Slim Framework](http://www.slimframework.com/) and [Zend Expressive](https://zendframework.github.io/zend-expressive/).


[![Latest Version](https://img.shields.io/packagist/v/tuupola/slim-basic-auth.svg?style=flat-square)](https://packagist.org/packages/tuupola/slim-basic-auth)
[![Packagist](https://img.shields.io/packagist/dm/tuupola/slim-basic-auth.svg)](https://packagist.org/packages/tuupola/slim-basic-auth)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://img.shields.io/travis/tuupola/slim-basic-auth/master.svg?style=flat-square)](https://travis-ci.org/tuupola/slim-basic-auth)
[![Coverage](http://img.shields.io/codecov/c/github/tuupola/slim-basic-auth/2.x.svg?style=flat-square)](https://codecov.io/gh/tuupola/slim-basic-auth/branch/2.x)


## Install

Install latest version using [composer](https://getcomposer.org/).

```
$ composer require tuupola/slim-basic-auth
```

## Usage

Configuration options are passed as an array. Only mandatory parameter is  `users`. This is an array where you pass one or more `"username" => "password"` combinations. Username is the key and password is the value.

```php
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

Same with Zend Expressive.

```php
$app = Zend\Expressive\AppFactory::create();

$app->pipe(new \Slim\Middleware\HttpBasicAuthentication([
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
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => '$2y$10$1lwCIlqktFZwEBIppL4ak.I1AHxjoKy9stLnbedwVMrt92aGz82.O',
        "somebody" => '$2y$10$6/vGXuMUoRlJUeDN.bUWduge4GhQbgPkm6pfyGxwgEWT0vEkHKBUW'
    ]
]));
```

Even if you are using hashed passwords it is not the best idea to store credentials in the code. Instead you could store them in environment or external file which is not committed to GitHub.

```php
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "users" => [
        "admin" => getenv("ADMIN_PASSWORD")
    ]
]));
```

## Optional parameters
### Path

The optional `path` parameter allows you to specify the protected part of your website. It can be either a string or an array. You do not need to specify each URL. Instead think of `path` setting as a folder. In the example below everything starting with `/api` will be authenticated.

``` php
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/api", /* or ["/admin", "/api"] */
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

### Passthrough

With optional `passthrough` parameter you can make exceptions to `path` parameter. In the example below everything starting with `/api` and `/admin`  will be authenticated with the exception of `/api/token` and `/admin/ping` which will not be authenticated.

``` php
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => ["/api", "/admin"],
    "passthrough" => ["/api/token", "/admin/ping"],
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

### Callback

Callback is called only when authentication succeeds. It receives an array containing `user` and `password` as argument. If callback returns boolean `false` authentication is forced to be failed.

```php
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ],
    "callback" => function ($request, $response, $arguments) {
        print_r($arguments);
    }
]));
```

## Security

Browsers send passwords over the wire basically as cleartext. You should always use HTTPS. If the middleware detects insecure usage over HTTP it will throw `RuntimeException`. This rule is relaxed for localhost. To allow insecure usage you must enable it manually by setting `secure` to `false`.


``` php
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "secure" => false,
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

Alternatively you can list your development host to have relaxed security.

``` php
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "secure" => true,
    "relaxed" => ["localhost", "dev.example.com"],
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
use \Slim\Middleware\HttpBasicAuthentication\AuthenticatorInterface;

class RandomAuthenticator implements AuthenticatorInterface {
    public function __invoke(array $arguments) {
        return (bool)rand(0,1);
    }
}

$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "authenticator" => new RandomAuthenticator()
]));
```

Same thing can also be accomplished with anonymous function.

```php
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
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
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/api",
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ],
    "error" => function ($request, $response, $arguments) {
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
use \Slim\Middleware\HttpBasicAuthentication\PdoAuthenticator;

$pdo = new \PDO("sqlite:/tmp/users.sqlite");
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
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

If this is not possible workaround is to pass credentials in an environment variable using mod_rewrite.

```
RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

The above rewrite rule should work out of the box. In some cases server adds `REDIRECT_` prefix to environment name. In this case or if you want to use nonstandard environment use the parameter called `environment`.

```php
$app = new \Slim\App;

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ],
    "environment" => "REDIRECT_HTTP_AUTHORIZATION"
]));
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

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email tuupola@appelsiini.net instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


