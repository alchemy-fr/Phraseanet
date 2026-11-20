CHANGELOG
---------

* 5.0.0

  * BC Break : require PHP 8.2+ and alchemy/binary-driver ^6.0
  * BC Break : UnoconvServiceProvider is a Pimple 3 service provider (`Pimple\ServiceProviderInterface`).
    `unoconv.configuration` is not overwritten anymore with the merged configuration.

* 0.3.0 (06-25-2013)

  * BC Break : Switch of Unoconv::create arguments.
  * BC Break : Service provider configuration has been simplified.

* 0.2.0 (04-26-2013)

  * Use alchemy/binary-driver as base for driver
  * Split unit tests and functional tests

* 0.1.1 (02-11-2013)

  * Adjust composer dependencies with tilde operator, allow larger range of versions.

* 0.1.0 (12-21-2012)

  * First stable version.
