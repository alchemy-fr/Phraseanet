CHANGELOG
---------

* 5.0.0

  * BC Break: require PHP 8.2+, alchemy/phpexiftool ^4.2, php-ffmpeg/php-ffmpeg ^1.4, symfony/http-foundation ^7.4,
    symfony/mime ^7.4 and doctrine/collections ^1.8 || ^2.0. Monolog is not required anymore.
  * BC Break: `MediaVorusServiceProvider` is a Pimple 3 service provider (`Pimple\ServiceProviderInterface`).
    It does not register the exiftool nor ffprobe services, the `exiftool.reader` and `exiftool.writer` services
    are required (checked when `mediavorus` is first accessed), `ffmpeg.ffprobe` is optional.
  * BC Break: `MediaVorus::create()` accepts an optional PHPExiftool classes directory and a logger.
  * BC Break: `MediaVorus\Utils\*MimeTypeGuesser` implement `Symfony\Component\Mime\MimeTypeGuesserInterface`
    (`guess()` is replaced by `guessMimeType()`), register them with `MimeTypes::getDefault()->registerGuesser()`.
  * JMS Serializer metadata are declared with PHP attributes.

* 0.4.4 (08-26-2014)

  * Fix width and height video extraction (@nlegoff).
  
* 0.4.3 (01-06-2014)

  * Fix video audio sample rate cast.

* 0.4.2 (11-28-2013)

  * Add support for video orientation.

* 0.4.1 (10-21-2013)

  * Add compatibility with PHP-FFMpeg 0.4.
  * Fix temporary files management.

* 0.4.0 (07-04-2013)

  * Add compatibility with PHP-FFMpeg 0.3.
  * BC Break : Implementation must now explicitely registered MediaVorus
    mimetype guessers as they are not automatically registered anymore.
  * Ensure FFProbe dependency is optional.

* 0.3.2 (04-16-2013)

  * Cleanup JMS Serializer / exiftool / PHPExiftool version dependencies

* 0.3.1 (01-31-2013)

  * Merge with 0.1.2
  * Register FileBinaryMimeTypeGuesser prior to FileInfoMimetypeGuesser

* 0.3.0 (01-14-2013)

  * Add support for media serialization via JMS serializer.

* 0.2.1 (12-21-2012)

  * Support of the latest PHPExiftool API.

* 0.2.0 (12-14-2012)

  * Update API.
  * Add support of videos Width/Height via FFProbe.

* 0.1.2 (02-07-2013)

  * Add more mime types to match the document format.

* 0.1.1 (12-14-2012)

  * Support for Video Width / Height extraction via FFProbe.

* 0.1.0 (11-26-2012)

  * First stable version.
