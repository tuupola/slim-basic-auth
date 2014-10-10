# Basic Auth Middleware for Slim

This middleware implements HTTP Basic Authentication for Slim Framework.

[![Author](http://img.shields.io/badge/author-@tuupola-blue.svg?style=flat-square)](https://twitter.com/tuupola)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://img.shields.io/travis/tuupola/slim-basic-auth/master.svg?style=flat-square)](https://travis-ci.org/tuupola/slim-basic-auth) 
[![HHVM Status](https://img.shields.io/hhvm/tuupola/slim-basic-auth.svg?style=flat-square)](http://hhvm.h4cc.de/package/tuupola/slim-basic-auth)

## Install

You can install the middleware using composer.

```
$ composer require tuupola/slim-basic-auth "0.*"
```

Or add the following manually to you composer.json file.

```javascript
{
    "require": {
        "slim/slim": "2.*",
        "tuupola/slim-basic-auth": "0.*"
    }
}
```

## Usage

Configuration options are passed as an array. Only mandatory parameter is  `users`. This is an array where you pass one or more `"username" => "password"` combinations. Username is the key and password is the value.

```php
$app = new \Slim\Slim();

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => "t00r",
        "user" => "passw0rd"
    ]
]));
```

With optional `path` parameter can authenticate only given part of your website. You can also change the displayed `realm` using the parameter with same name.

```php
$app = new \Slim\Slim();

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "user" => "passw0rd"
    ]
]));
```

## Custom authentication methods

Sometimes passing users in an array is not enough. To authenticate against custom datasource you can create authenticator class. Authenticator must implement `authenticate($user, $pass)` method. It must return either `true` or `false`.

If you are creating an Enterprise&trade; software which randomly lets people log in you could use the following.


```php
use \Slim\Middleware\HttpBasicAuthentication\AuthenticatorInterface;

class RandomAuthenticator implements AuthenticatorInterface {
    public function authenticate($user, $pass) {
        return (bool)rand(0,1);
    }
}

$app = new \Slim\Slim();

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "authenticator" => new RandomAuthenticator()
]));
```


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
$app = new \Slim\Slim();

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/admin",
    "realm" => "Protected",
    "users" => [
        "root" => "t00r",
        "user" => "passw0rd"
    ],
    "environment" => "REDIRECT_HTTP_AUTHORIZATION"
]));
```


