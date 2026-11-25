# Geonames API Connector

[![Build Status](https://travis-ci.org/alchemy-fr/GeonamesServer-PHP-Plugin.png?branch=master)](https://travis-ci.org/alchemy-fr/GeonamesServer-PHP-Plugin)

This is a consumer for the [GeonamesServer](https://github.com/alchemy-fr/GeonamesServer).

## Usage

 - Connector creation :

```php
$connector = ALchemy\Geonames\Connector::create($serverUrl);
```

 - Query cities :

```php
// returns an array of Alchemy\Geonames\Geoname objects
$geonames = $connector->search('Paris');
```

Query options :

```php
// limit to 50 results
// use '89.73.4.152' as IP address for closeness sort
$connector->search('Paris', 50, '89.73.4.152');
```

 - Find by IP :

```php
// returns a Alchemy\Geonames\Geoname object
$geoname = $connector->ip('89.73.4.152');
```

 - Find a GeonameId :

```php
// returns a Alchemy\Geonames\Geoname object
$geoname = $connector->geoname(2988507);
```

## Pimple Service Provider

```php
$app = new Pimple\Container();
$app->register(new Alchemy\Geonames\GeonamesServiceProvider(), array(
    'geonames.server-uri' => 'http://geonames.domain.tld',  // mandatory parameter
));
```

## License

This project is released under the MIT License.
