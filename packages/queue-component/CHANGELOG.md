# CHANGELOG

## Unreleased

No unreleased changes

## 5.0.0

- Require PHP 8.2+, ramsey/uuid 4.7 and psr/log 1, 2 or 3.
- Support ext-amqp 2.x: `AmqpMessageQueue::publish()` throws `MessagePublishingException` when the exchange fails.
- `setLogger()` methods return `void`.

## [0.1.5] - 2016-10-05
### Added
  - `MessageQueueRegistry::getConfigurations()` to retrieve available queue configurations

## [0.1.4] - 2016-10-03
### Added
  - Queue registry class

## [0.1.3] - 2016-09-07
### Added
  - Add `timeout` option to AMQP configuration for consume timeouts

## [0.1.2] - 2016-08-01
### Added
  - Add dependency on psr/log to enable debug logging in queue wrappers
  
### Removed
  - Removed default dead letter exchange parameter value, must be explicitely configured from now on to be enabled
  
## [0.1.1] - 2016-07-29
### Added
  - Add a changelog
  - Add `AmqpFactory::create()` factory method to simplify initialisation

### Fixed
  - Quickstart code sample contained an error

## [0.1.0] - 2016-03-27
### Added
  - First version, compatible with AMQP extension
