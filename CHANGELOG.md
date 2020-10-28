# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.


# [3.3.1](https://github.com/tuupola//slim-basic-auth/compare/3.3.0...3.3.1) - 2020-10-28
### Fixed
- Bump minimum requirement of `tuupola/http-factory` to `1.0.2` . This is to avoid Composer 2 installing the broken `1.0.1` version which will also cause `psr/http-factory` to be removed. ([#103](https://github.com/tuupola/slim-basic-auth/pull/103))

# [3.3.0](https://github.com/tuupola//slim-basic-auth/compare/3.2.1...3.3.0) - 2020-09-23

### Added
- Allow installing with PHP 8 ([#99](https://github.com/tuupola/slim-basic-auth/pull/99/files)).

# [3.2.1](https://github.com/tuupola//slim-basic-auth/compare/3.2.0...3.2.1) - 2018-10-15
### Added
- Support for tuupola/callable-handler:^1.0 and tuupola/http-factory:^1.0

### Changed
- `psr/http-message:^1.0.1` is now minimum requirement.

# [3.2.0](https://github.com/tuupola//slim-basic-auth/compare/3.1.0...3.2.0) - 2018-08-07
### Added
- Support for the stable version of PSR-17

## [3.1.0](https://github.com/tuupola/slim-basic-auth/compare/3.0.0...3.1.0) - 2018-05-06
### Added
- Option to trust `X-Forwarded-Proto` and `X-Forwarded-Port` when detecting https requests.

## [3.0.0](https://github.com/tuupola/slim-basic-auth/compare/2.3.0...3.0.0) - 2018-03-01

### Changed
- Namespace changed from `Slim\Middleware` to `Tuupola\Middleware`
- Middleware now uses only `Authorization` header from the PSR-7 request. Both `PHP_AUTH_USER` and `PHP_AUTH_PW` globals as well as `HTTP_AUTHORIZATION` environment are now ignored.
- The `callback` setting was renamed to `before`. It is called before executing other middlewares in the stack.
- The `passthrough` setting was renamed to `ignore`.
- Public setter methods `addRule()` and `withRules()` are now immutable.
- PSR-7 double pass is now supported via [tuupola/callable-handler](https://github.com/tuupola/callable-handler) library.
- PHP 7.1 is now minimal requirement.
- Error callback now receives only response and arguments, request was removed.
- Before callback now receives only request and arguments, response was removed.
- After callback now receives only response and arguments, request was removed.

### Added
- Support for the [approved version of PSR-15](https://github.com/php-fig/http-server-middleware).
- New `after` callback. It is called after executing other middlewares in the stack.

### Removed
- Most setters and getters for settings. Pass settings in an array only during initialization.

## 2.3.0 - 2017-09-19

### Added

- Username is now passed to `error` callback when authentication fails.

```php
$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "users" => [
        "root" => "t00r",
        "somebody" => "passw0rd"
    ],
    "error" => function ($request, $response, $arguments) {
        var_dump($arguments["user"]);
        var_dump($arguments["message"]);
    }
]));
```

## 2.2.2 - 2017-02-27

This is a security release.

`RequestPathRule` now removes multiple slashes from the URI before determining whether the path should be authenticated or not. For HTTP client `/foo` and `//foo` are different URIs and technically valid according to [RFC3986](https://tools.ietf.org/html/rfc3986). However on serverside it depends on implementation and often `/foo`, `//foo` and even `/////foo` are considered a same route.

Different PSR-7 implementations were behaving in different way. Diactoros [removes multiple leading slashes](https://github.com/zendframework/zend-diactoros/blob/master/CHANGELOG.md#104---2015-06-23). By default Slim does not alter any slashes. However when installed in subfolder [Slim removes all slashes](https://github.com/slimphp/Slim/issues/1554).

This means if you are authenticating a subfolder, for example `/api` and Slim is installed in document root it was possible to bypass authentication by doing a request to `//api`. Problem did not exist if Slim was installed in subfolder. Diactoros was not affected.

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

### Fixed

- Ported fix for bug [slim-jwt-auth/50](https://github.com/tuupola/slim-jwt-auth/issues/50) where in some cases it was possible to bypass authentication by adding multiple slashes to request URI.

## Older

I was lazy and did no keep a changelog before this.