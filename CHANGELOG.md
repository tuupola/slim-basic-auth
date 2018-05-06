# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [3.1.0](https://github.com/tuupola/slim-basic-auth/compare/3.1.0...3.0.0) - unreleased
### Added
- Option to trust `X-Forwarded-Proto` and `X-Forwarded-Port` when detecting https requests.

## [3.0.0](https://github.com/tuupola/slim-basic-auth/compare/3.0.0...2.3.0) - 20180301

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


