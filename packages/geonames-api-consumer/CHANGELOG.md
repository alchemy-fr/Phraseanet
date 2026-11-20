CHANGELOG
=========

### 5.0.0

  * Require PHP 8.2+ and Guzzle 7 (`guzzlehttp/guzzle`), `Connector` expects a `GuzzleHttp\ClientInterface`.
  * `GeonamesServiceProvider` is a Pimple 3 provider; a missing `geonames.server-uri` is reported when
    `geonames.connector` is first accessed.

### 0.1.2 (2014-02-05)

  * Add support for Monolog logger.

### 0.1.1 (2012-07-02)

  * Add better support for HTTP errors.

### 0.1.0 (2012-07-02)

  * First tagged release.
