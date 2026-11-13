CHANGELOG
---------

* 5.0.0

   * BC Break: require PHP 8.2+, pimple/pimple ^3.6, imagine/imagine ^1.5, php-ffmpeg/php-ffmpeg ^1.4,
     alchemy/phpexiftool ^4.2, alchemy/mediavorus ^5.0, alchemy/ghostscript ^5.0, php-unoconv/php-unoconv ^5.0,
     php-mp4box/php-mp4box ^5.0, neutron/temporary-filesystem ^4.0 and Symfony ^7.4 components.
   * BC Break: drop SwfTools support: `Specification\Flash`, `SpecificationInterface::TYPE_SWF`,
     `Transmuter\Document2Flash`, `Transmuter\Flash2Image` and the `swftools.*` drivers and configuration
     keys have been removed.
   * BC Break: `DriversContainer` extends `Pimple\Container` (Pimple 3), the default `logger` is a
     `Psr\Log\NullLogger` and the `logger.name`, `logger.level`, `logger.handler` and `logger.class` keys
     have been removed. `ffmpeg.ffprobe.cache` is a PSR-6 cache pool.
   * BC Break: exiftool drivers are built with PHPExiftool 4, tag classes are generated in the directory
     configured with the `exiftool.classes-directory` configuration key (defaults to a directory in the system
     temporary directory). The exiftool binary can be configured with `exiftool.binary`.
   * BC Break: `MediaAlchemystServiceProvider` is a Pimple 3 service provider (`Pimple\ServiceProviderInterface`).
   * BC Break: `Specification\Image::RESIZE_MODE_INBOUND` and `RESIZE_MODE_OUTBOUND` follow Imagine 1.x
     thumbnail constants values.
   * Background color and alpha removal (`Specification\Image::setBackgroundColor()` / `setFlatten()`) are
     implemented on top of the Imagine API.

* 0.4.8 (02-03-2015)

   * Add 'disable-alpha' in specifications

* 0.4.7 (01-14-2015)

  * Enhance memory management
  * Enhance Animation specification

* 0.4.6 (09-10-2014)

  * Update mediavorus & php-ffmpeg dependencies.

* 0.4.5 (08-26-2014)

  * Add flatten property for image specification.

* 0.4.4 (01-31-2014)

  * Avoid interpolation for image and videos.

* 0.4.3 (12-03-2013)

  * Fix autorotation and spec rotation conflict.

* 0.4.2 (12-02-2013)

  * Remove debug.

* 0.4.1 (12-02-2013)

  * Fix video to animation auto-rotation.

* 0.4.0 (12-02-2013)

  * Fix image auto-rotation.
  * Add video auto-rotation.
  * BC Break : PHP-FFmpeg 0.4 is required

* 0.3.6 (10-21-2013)

  * Add compatibility with PHP-FFMpeg 0.4.

* 0.3.5 (09-05-2013)

  * Use PHP-FFMpeg display ratio fixer filter for frames extraction.

* 0.3.4 (08-08-2013)

  * Enhance support for Photoshop files.

* 0.3.3 (08-07-2013)

  * Use TemporaryFilesystem Manager to manage temporary files.

* 0.3.2 (07-22-2013)

  * Merge with 0.1.7, fix image flatten.

* 0.3.1 (07-22-2013)

  * Use Imagine 0.6.x, forget custom fork.

* 0.3.0 (07-04-2013)

  * Update PHP FFMpeg to 0.3
  * Update PHP Ghostscript to 0.4
  * Update MediaVorus to 0.4
  * Update PHP Unoconv to 0.3
  * Update PHP MP4Box to 0.3
  * Update PHP Swf Tools to 0.3
  * BC Break in Alchemyst API
  * BC Break in MediaAlchemystServiceProvider configuration

* 0.2.5 (04-19-2013)

  * Support for Imagine Animated gifs (@romainneutron branch rebased)

* 0.2.4 (04-16-2013)

  * Remove exiftool dependency

* 0.2.3 (03-21-2013)

  * Support for Imagine ColorSpaces (@romainneutron branch)

* 0.2.2 (02-01-2013)

  * Use Ghostscript 0.2

* 0.2.1 (01-31-2013)

  * Fix Composer issues

* 0.2.0 (12-21-2012)

  * Update API, Use Pimple as driver container.
  * Update to MediaVorus 0.2.*

* 0.1.6 (04-19-2013)

  * Support for Imagine Animated gifs (@romainneutron branch rebased)

* 0.1.5 (03-21-2013)

  * Support for Imagine ColorSpaces (@romainneutron branch)

* 0.1.4 (12-12-2012)

  * Support for Imagine 0.4, extract biggest layer of a Tiff image.

* 0.1.2 (10-24-2012)

  * Add support for PHP-Ghostscript for Document->Image transmuter

* 0.1.0 (07-18-2012)

  * First stable version.
