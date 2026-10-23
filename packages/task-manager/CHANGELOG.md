CHANGELOG
---------

* 6.0.0

  * Require PHP 8.2+, Symfony EventDispatcher 7.4, Symfony Process 7.4 and neutron/process-manager 6.0.
  * Events extend `Symfony\Contracts\EventDispatcher\Event` and are dispatched with `dispatch($event, $eventName)`.
  * `AbstractJob::setLogger()` and `TaskManager::setLogger()` return `void` (PSR-3 `LoggerAwareInterface` compatibility).

* 2.0.0 (xx-xx-2014)

  * JobDataInterface not implements __toString().

* 1.0.1 (12-03-2013)

  * Add compatibility with Symfony 2.4.0.

* 1.0.0 (10-29-2013)

  * First stable version.
