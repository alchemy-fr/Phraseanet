CHANGELOG
---------

* 5.0.0

  * BC Break: require PHP 8.2+ and alchemy/binary-driver ^6.0
  * BC Break: `XPDFServiceProvider` is a Pimple 3 service provider (`Pimple\ServiceProviderInterface`)
  * Fix `PdfInfo::extractInfo`
  * Pass a valid `-eol unix` option to pdftotext

* 0.2.1 (07-03-2013)

  * Revert `PdfTotext::setPageQuantity`

* 0.2.0 (07-03-2013)

  * BC Break : `PdfToText::open` and `PdftoText::close` methods are removed. PdfTotext is now a service.
  * Use alchemy/binary-adapter as driver base.

* 0.1.1 (01-31-2013)

  * Remove unstable dependencies
  * Allow wider version intervals for dependencies

* 0.1.0 (05-31-2012)

  * First stable version.
