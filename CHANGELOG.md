# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.2.2 - 2017-02-27

This is a security release.

`RequestPathRule` now removes multiple slashes from the URI before determining whether the path should be authenticated or not. For client `/foo` and `//foo` are different URIs. On server side it depends on implementation but they usually map to the same route action.

Different PSR-7 implementations were behaving in different way. Diactoros [removes multiple leading slashes](https://github.com/zendframework/zend-diactoros/blob/master/CHANGELOG.md#104---2015-06-23) while Slim does not.

This means if you are authenticating a subfolder, for example `/api`, with Slim it was possible to bypass authentication by doing a request to `GET //api`. Diactoros was not affected.

```php
$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/api",
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

If you were using default setting of authenticating all routes you were not affected.

```php
$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ]
]));
```

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Ported fix for bug [slim-jwt-auth/50](https://github.com/tuupola/slim-jwt-auth/issues/50) where in some cases it was possible to bypass authentication by adding multiple slashes to request URI.

