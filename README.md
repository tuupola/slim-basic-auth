# Basic Auth Middleware for Slim [![Build Status](https://api.travis-ci.org/tuupola/slim-basic-auth.png?branch=master)](https://travis-ci.org/tuupola/slim-basic-auth)

This middleware implements HTTP Basic Authentication for Slim Framework.

## Install

You can install the middleware using composer.

```javascript
{
    "require": {
        "tuupola/slim-basic-auth": "dev-master",
    }
}
```

## Usage

Configuration options are passed as an array. Only mandatory parameter is  `users`. This is an array where you pass one or more `"username" => "password"` combinations. Username is the key and password is the value.

```php
$app = new \Slim\Slim();

$app->add(new \Slim\Middleware\HttpBasicAuth(array(
    "users" => array(
        "root" => "t00r",
        "user" => "passw0rd"
    )
)));
```

With optional `path` parameter can authenticate only given part of your website. You can also change the displayed `realm` using the parameter with same name.

```php
$app = new \Slim\Slim();

$app->add(new \Slim\Middleware\HttpBasicAuth(array(
    "path" => "/admin",
    "realm" => "Protected",
    "users" => array(
        "root" => "t00r",
        "user" => "passw0rd"
    )
)));
```

## Usage with FastCGI

When using Apache with FastCGI the credentials [do not get passed to the PHP script](https://bugs.php.net/bug.php?id=35752). Workaround is to pass credentials in environment variable using mod_rewrite.

```
RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

The above rewrite rule should work out of the box. In some cases server adds `REDIRECT_` prefix to environment name. In this case or if you want to use nonstandard environment use the parameter called `environment`.

```php
$app = new \Slim\Slim();

$app->add(new \Slim\Middleware\HttpBasicAuth(array(
    "path" => "/admin",
    "realm" => "Protected",
    "users" => array(
        "root" => "t00r",
        "user" => "passw0rd"
    ),
    "environment" => "REDIRECT_HTTP_AUTHORIZATION"
)));
```
