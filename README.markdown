# Basic Auth Middleware for Slim

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

There is an additional optional parameter named `cgi_auth_var_name`: in case PHP is running as CGI and since Apache doesn't pass HTTP Basic Authentication information to CGI apps, the authorization tokens should be passed as an environment variable using mod_rewrite rule, like this:
`RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`
Sometimes additional `REDIRECT_` prefix may be added by the server to the variable name, so the resulting name could be something like  `REDIRECT_HTTP_AUTHORIZATION` or even `REDIRECT_REDIRECT_HTTP_AUTHORIZATION`.

```php
$app = new \Slim\Slim();

$app->add(new \Slim\Middleware\HttpBasicAuth(array(
    "path" => "/admin",
    "realm" => "Protected",
    "users" => array(
        "root" => "t00r",
        "user" => "passw0rd"
    ),
    "cgi_auth_var_name" => "REDIRECT_HTTP_AUTHORIZATION"
)));
```