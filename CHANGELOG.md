# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [3.0.0](https://github.com/tuupola/slim-basic-auth/compare/3.0.0-rc.3...2.3.0) - Unreleased
### Changed
- Namespace changed from `Slim\Middleware` to `Tuupola\Middleware`
- Middleware now uses only `Authorization` header from the PSR-7 request. Both `PHP_AUTH_USER` and `PHP_AUTH_PW` globals as well as `HTTP_AUTHORIZATION` environment are now ignored.
- The `callback` setting was renamed to `before`. It is called before executing other middlewares in the stack.
- The `passthrough` setting was renamed to `ignore`.
- Public setter methods `addRule()` and `withRules()` are now immutable.
- PSR-7 double pass is now supported via [tuupola/callable-handler](https://github.com/tuupola/callable-handler) library.

### Added
- New `after` callback. It is called after executing other middlewares in the stack.

### Removed
- Most setters and getters for settings. Pass settings in an array only during initialization.
- Support for PHP 5.X. PSR-15 is now PHP 7.x only.


