# CHANGELOG

## Unreleased
  - No unreleased changes

## [5.0.0]
### Changed
  - Require PHP 8.2+, Symfony Process 7.4, psr/log 1, 2 or 3 and alchemy/queue-component 5.0.
  - `ProcessPool` builds `Symfony\Component\Process\Process` instances directly (no more `ProcessBuilder`).
  - `setLogger()` methods return `void`.

## [0.1.2] - 2016-10-10
### Fixed
  - Do not set environment arg for worker command when not configured

## [0.1.1] - 2016-10-06
### Added
  - Callable worker factory which allows worker creation using callables
  - License headers

## [0.1.0] - 2016-10-03
### Added
  - Initial release
