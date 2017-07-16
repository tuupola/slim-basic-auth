# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [Unreleased]
### Changed
- Namespace changed from `Slim\Middleware` to `Tuupola\Middleware`
- Middleware now uses only `Authorization` header from the PSR-7 request. Both `PHP_AUTH_USER` and `PHP_AUTH_PW` globals as well as `HTTP_AUTHORIZATION` environment are now ignored.
- The `callback` setting was renamed to `before`. It is called before executing other middlewares in the stack.
- The `passthrough` setting was renamed to `ignore`.
- Public setter methods `addRule()` and `withRules()` are now immutable.

### Added
- New `after` callback. It is called after executing other middlewares in the stack.

### Deprecated
### Removed
- Most setters and getters for settings. Pass settings in an array only during initialization.

### Fixed
### Security


