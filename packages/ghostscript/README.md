# Ghostscript PHP driver


# API usage

To instantiate Ghostscript driver, the easiest way is :

```php
$transcoder = Ghostscript\Transcoder::create();
```

You can customize your driver by passing a `Psr\Log\LoggerInterface` or
configuration options.

Available options are :

 - `gs.binaries` : the path (or an array of potential paths) to the ghostscript binary.
 - `timeout` : the timeout for the underlying process.

```php
$transcoder = Ghostscript\Transcoder::create(array(
    'timeout' => 42,
    'gs.binaries' => '/opt/local/gs/bin/gs',
), $logger);
```

To process a file to PDF format, use the `toPDF` method :

Third and fourth arguments are respectively the first page and the number of
page to transcode.

```php
$transcoder->toPDF('document.pdf', 'first-page.pdf', 1, 1);
```

To render a file to Image, use the `toImage` method :

```php
$transcoder->toImage('document.pdf', 'output.jpg');
```

## Pimple Service Provider :

A [Pimple](https://github.com/silexphp/Pimple) Service Provider is available, all parameters
are optionals :

```php
$app = new Pimple\Container();
$app->register(new Ghostscript\GhostscriptServiceProvider(), array(
    'ghostscript.configuration' => array(
        'gs.binaries' => '/usr/bin/gs',
        'timeout'     => 42,
    )
    'ghostscript.logger' => $logger, // a PSR-3 logger
));

$app['ghostscript.pdf-transcoder']->toImage('document.pdf', 'image.jpg');
```

# License

Released under the MIT License
