# CHANGELOG

* 3.9.0 (xxxx-xx-xx)

  - Convert Orders custom adapter to Doctrine entity.
  - Convert Feeds custom adapter to Doctrine entity.
  - Convert Users custom adapter to Doctrine entity.
  - Convert Ftp Export custom adapter to Doctrine entity.
  - Convert Ftp Export custom adapter to Doctrine entity.
  - Session management is now part of Phraseanet configuration.

## 3.8.3 (2014-xx-xx)

  - BugFix : Fix record type editing.
  - BugFix : Fix scheduler timeout.
  - BugFix : Fix thesaurus tab javascript errors.
  - BugFix : Fix IE slow script error messages.
  - BugFix : Fix basket records sorting.
  - BugFix : Fix admin field editing on a field delete.
  - BugFix : Fix HTTP 400 on email test.
  - BugFix : Fix records export names.
  - BugFix : Fix collection rights injection on create.
  - BugFix : Fix disconnection of removed users.
  - BugFix : Fix language selection on mobile devices.
  - BugFix : Fix collection and databox popups in admin views.
  - BugFix : Fix suggested values editing on Firefox.
  - BugFix : Fix lightbox that could not be load in case some validation have been removed.
  - BugFix : Fix user settings precedence.
  - BugFix : Fix user search by last applied template.
  - BugFix : Fix thesaurus highlight.
  - BugFix : Fix collection sorting.
  - BugFix : Fix FTP test messages.
  - BugFix : Fix video width and height extraction.
  - BugFix : Fix caption sanitization.
  - BugFix : Fix report locales.
  - BugFix : Fix FTP receiver email reception.
  - BugFix : Fix user registration management display.
  - BugFix : Fix report icons.
  - BugFix : Fix report pagination.
  - BugFix : Fix Phrasea SearchEngine cache duration.
  - BugFix : Fix basket caption display.
  - BugFix : Fix collection mount.
  - BugFix : Fix password grant authorization in API.
  - BugFix : Fix video display on mobile devices.
  - BugFix : Fix record mover task.
  - BugFix : Fix bug on edit presets load.
  - BugFix : Fix detailed view access by guests users.
  - Enhancement : Add datepicker input placeholder.
  - Enhancement : Add support for portrait videos.
  - Enhancement : Display terms of use in a new window.
  - Enhancement : Increase tasks memory limit.
  - Enhancement : Add an option to reset advanced search on production reload.
  - Enhancement : Update task manager log messages.
  - Enhancement : Update to Symfony 2.3.9.
  - Enhancement : Add plugins:list command.
  - Enhancement : Images and Videos are not interpolated anymore.
  - Enhancement : Add option to disable filesystem logs.
  - Enhancement : Add compatibility with PHP 5.6.

## 3.8.2 (2013-11-15)

  - BugFix : Locale translation may block administration module load.

## 3.8.1 (2013-11-15)

  - BugFix : IE 6 homepage error message is broken.
  - BugFix : Databox fields administration is broken on firefox.
  - BugFix : Report CSS is broken.
  - BugFix : Databox fields administration has some behavior bugs.
  - BugFix : Install data-path is not saved.
  - BugFix : Third-party applications are displayed disabled when enabled and vice-versa.
  - BugFix : Increase tasks default memory limit.
  - BugFix : Oauth2 password grant_type authentication is broken.
  - BugFix : CSS issues on mobile devices.
  - BugFix : Editing records from multiple databoxes triggers a fatal error.
  - BugFix : API search query is discarded with GET method.
  - BugFix : Wrong offset for Classic query result.
  - BugFix : API does not return SearchEngine suggestions correctly.
  - BugFix : SearchEngine collection filter does not work in Classic.
  - BugFix : Unable to start scheduler on Windows platform.
  - BugFix : Resizing images is broken on mobile devices in landscape mode.
  - BugFix : Text input color is not correctly rendered on old IEs.
  - BugFix : IE11 is not recognize as HTML5 compatible.
  - BugFix : Disallow push when records can not be pushed.
  - BugFix : Upgrade data command fails.
  - BugFix : Export by mail fails.
  - BugFix : ACL cache issue.
  - BugFix : Registration collection auto-selection is broken.
  - BugFix : Allow thesaurus browsing to non-thesaurus-admins.
  - BugFix : Datepickers displays incorrectly on firefox.
  - BugFix : Bridge playlists loading fails.
  - BugFix : Editing modal box is broken on IE7.
  - BugFix : A user can remove himself from the admin panel.
  - BugFix : Basket export fails.
  - BugFix : Allow stemmed search only if stemming is enabled.
  - BugFix : Reset date sort to the correct value on advanced-search reset.
  - BugFix : Disable SQL logging when in non-dev environment.
  - BugFix : Task-Manager scheduler randomly stops.
  - BugFix : Increase usr_login size, display error if login is longer than possible.
  - Enhancement : Allow default user settings customisation.
  - Enhancement : Propose rights reset prior apply template.
  - Enhancement : Enhance CSS selector for IE performance.
  - Enhancement : Sanitize caption XML values.
  - Enhancement : Add checkbox on feed creation to disable email notifications.
  - Enhancement : Add Bootstrap Carousel & Galleria to homepage presentation mode.
  - Enhancement : Push or feedback names are now mandatory.
  - Enhancement : Add Phraseanet twig namespace.
  - Enhancement : Allow video bitrate up to 12M.

## 3.8.0 (2013-09-26)

  - BC Break : Removed `bin/console check:system` command, replaced by `bin/setup check:system`.
  - BC Break : Removed `bin/console system:upgrade` command, replaced by `bin/setup system:upgrade`.
  - BC Break : Removed `bin/console check:ensure-production-settings` and `bin/console check:ensure-dev-settings`
    commands, replaced by `bin/console check:config`.
  - BC break : Configuration simplification, optimized for performances.
  - BC Break : Time limits are now applied on templates application.

  - SwiftMailer integration (replaces PHPMailer).
      - Emails now include an HTML view.
      - Emails can now have a customized subject prefix.
      - Emails can be sent to SMTP server using TLS encryption (only SSL was supported).
  - Sphinx-Search is now stable (require Sphinx-Search 2.0.6).
  - Add support for stemmatisation in Phrasea-Engine.
  - Add bin/setup command utility, it is now recommanded to use `bin/setup system:install`
    command to install Phraseanet.
  - Lots of cleanup and code refactorisation.
  - Add bin/console mail:test command to check email configuration.
  - Admin databox structure fields editing redesigned.
  - Refactor of the configuration tester.
  - Refactor authentication, add support for external authentication providers
      - Support for Facebook, Twitter, Viadeo, Github, Linkedin, Google-Plus.
  - Add `Link` header in permalink resources HTTP responses.
  - Global speed improvement on report.
  - Upload now monitors number of files transmitted.
  - Add bin/developer console for developement purpose.
  - Add possibility to delete a basket from the workzone basket browser.
  - Add localized labels for databox documentary fields.
  - Add localized labels for databox collections.
  - Add localized labels for databox status-bits.
  - Add localized labels for databox names.
  - Add plugin architecture for third party modules and customization.
  - Add records sent-by-mail report.
  - User time limit restrictions can now be set per databox.
  - Add gzip/bzip2 options for DBs backup commandline tool.
  - Add convenient XSendFile configuration tools in bin/console :
      - bin/console xsendfile:configuration-generator that generates your
        xsendfile mapping depending on databoxes configuration.
      - bin/console xsendfile:configuration-dumper that dumps your virtual
        host configuration depending on Phraseanet configuration
  - Phraseanet enabled languages is now configurable.

## 3.7.15 (2013-09-14)

  - Add Office Plugin client id and secret.

## 3.7.14 (2013-07-23)

  - BugFix : Multi layered images are not rendered properly.
  - BugFix : Status editing can be accessed on some records by users that are not granted.
  - BugFix : Records index is not updated after databox structure field rename.
  - Enhancement : Add support for grayscale colorspaces.

## 3.7.13 (2013-07-04)

  - Some users were able to access story creation form whereas they were not allowed to.
  - Disable detailed view keyboard shortcuts when export modal is open.
  - Update to PHP-FFMpeg 0.2.4, better support for video resizing.
  - BugFix : Unablt to reject a thesaurus term from thesaurus module.

## 3.7.12 (2013-05-13)

  - BugFix : : Removed "required" attribute on non-required fields in order form.
  - BugFix : : Fix advanced search dialog CSS.
  - BugFix : : Grouped status bits are not displayed in advanced search dialog.
  - Enhancement : Locales update.

## 3.7.11 (2013-04-23)

  - Enhancement : Animated Gifs (video support) does not requir Gmagick anymore to work properly.
  - BugFix : : When importing users from CSV file, some properties were missing.
  - BugFix : : In Report, CSV export is limited to 30 lines.

## 3.7.10 (2013-04-03)

  - BugFix : : Permalinks pages may be broken.
  - BugFix : : Permalinks always expose the file extension of the original document.
  - BugFix : : Thesaurus multi-bases queries may return incorrect proposals.
  - BugFix : : Phraseanet installation fails.
  - BugFix : : Consecutive calls to image tools may fail.

## 3.7.9 (2013-03-27)

  - BugFix : : Detailed view does not display the right search result.
  - BugFix : : Twitter and Facebook share are available even if it's disabled in system settings.
  - Add timers in API.
  - Permalinks now expose a filename.
  - Permalinks returned by the API now embed a download URL.
  - Bump to API version 1.3.1 (see https://docs.phraseanet.com/3.7/en/Devel/API/Changelog.html).

## 3.7.8 (2013-03-22)

  - BugFix : : Phraseanet API does not return results at correct offset.
  - BugFix : : Manual thumbnail extraction for videos returns images with anamorphosis.
  - BugFix : : Rollover images have light anamorphosis.
  - BugFix : : Document and sub-definitions substitution may not work properly.
  - Add preview and caption to order manager.
  - Add support for CMYK images.
  - Preserve ICC profiles data in sub-definitions.

## 3.7.7 (2013-03-08)

  - BugFix : : Archive task fails with stories.
  - Update of dutch locales.
  - BugFix : : Fix feeds entry notification display.
  - BugFix : : Read receipts are not associated to email for push and validation.

## 3.7.6 (2013-02-01)

  - BugFix : : Load of a publication entry with a publisher that refers to a deleted users fails.
  - BugFix : : Wrong ACL check for displaying feeds in Lightbox (thumbnails are displayed instead of preview).
  - Releasing a validation feedback now requires at least one agreement.
  - BugFix : : Lightbox zoom fails when image is larger than container.
  - BugFix : : Landscape format images are displayed with a wrong ratio in quarantine.
  - General enhancement of Lightbox display on IE 7/8/9.

## 3.7.5 (2013-01-09)

  - Support of Dailymotion latest API.
  - BugFix : : Bridge application creation is not possible after having upload a file.
  - Upload speed is now in octet (previously in bytes).
  - Upload is de-activated when no data box is mounted.
  - BugFix : : Lightbox display is broken on IE 7/8.
  - BugFix : : Collection setup via console throws an exception.
  - BugFix : : Metadata extraction via Dublin Core mapping returns broken data.
  - BugFix : : Minilogos with a size less than 24px are resized.
  - BugFix : : Watermark custom files are not handled correctly.
  - BugFix : : XML import to metadata fields that do not have proper source do not work correctly.
  - BugFix : : Databox unmount can provide 500's to users that have attached stories to their work zone.

## 3.7.4 (2012-12-20)

  - BugFix : : Upgrade from 3.5 may lose metadatas.
  - BugFix : : Selection of a metadata source do not behave correctly.
  - BugFix : : Remember collections selection on production reload.
  - BugFix : : Manually renew a developer token fails.
  - BugFix : : Terms Of Use template displays HTML entitites.
  - Replace javascript alert by Phraseanet dialog box in export dialog box.
  - Video subdef GOP option has now 300 as max value with steps of 10.
  - BugFix : : Some subdef options are not saved correctly (audio samplerate, GOP).
  - Support for multi-layered tiff.
  - BugFix : : Long collection names are not displayed correctly.
  - BugFix : : Document permalinks were not correctly supported.
  - BugFix : : Export name containing non ASCII are now escaped.
  - Default structure now have a the thumbtitle attribute correctly set up.
  - Chrome mobile User Agent is now supported.
  - BugFix : : Remove minilogos do not work.
  - BugFix : : Send orders do not triggers notifications.
  - BugFix : : Story thumbnails are not displayed correctly.
  - BugFix : : Add dutch (nl_NL) support.

## 3.7.3 (2012-11-09)

  - BugFix : : Security flaw (thanks TEHTRI-Security http://www.tehtri-security.com/).
  - BugFix : : Thesaurus issue when a term contains HTML entity.
  - BugFix : : Video width and height are now multiple of 16.
  - BugFix : : Download over HTTPS on IE 6 fails.
  - BugFix : : Permalinks that embeds PDF are broken.
  - BugFix : : Lightbox shows record preview at load even if the user does not have the right to access it.
  - BugFix : : Reminders that have been sent to validation participants are not saved.
  - BugFix : : IE 6 is now correctly handled in Classic module.
  - BugFix : : Download of a basket with a title containing a slash ('/') fails.
  - BugFix : : Add check on posix extension alongside pcntl extension.
  - BugFix : : Some process may fail with pcntl extension.
  - File-info mime-type guesser is deprecated in favor of binary mime-type guesser.
  - Add an option to force Terms of Use re-validation for each export.
  - Move binary configuration to config file (config/binaries.yml).
  - Lazy load thumbnails in result view.
  - When duplicating rights (at collection creation), quotas, masks and time-restrictions are now copied.
  - Add Wincache support.
  - Add Dutch localization.
  - Add Expiration cache strategy for thumbnail-class sub definitions.
  - Display job and company in Push / Validation user search results.
  - Add a captcha field for registration.
  - Bridge accounts are now deletable.
  - Mails links are now clickable in Thunderbird and Outlook.
  - Emails list in mail export now supports comma and space separators.

## 3.7.2 (2012-10-04)

  - Significant speed enhancement on thumbnail display.
  - Add a purge option to quarantine.
  - BugFix : ascending date sort in search results.
  - Multiple thesaurus fixes on IE.
  - BugFix : description field source selection.
  - Add option to rotate multiple image.
  - `Remember-me` was applied even if the box was not checked.

## 3.7.1 (2012-09-18)

  - Multiple fixes in archive task.
  - Add options -f and -y to upgrade command.
  - Add a Flash fallback for browser that does not support HTML5 file API.
  - BugFix : upgrade from version 3.1 and 3.5.
  - BugFix : : Print tool is not working on IE version 8 and less over HTTPS.

## 3.7.0 (2012-07-24)

  - Lots of graphics enhancements.
  - Windows Server 2008 support.
  - Add business fields.
  - Add new video formats (HTML5 compatibility).
  - Add target devices for subviews.
  - Thumbnail extraction tool for videos.
  - Upgrade of the Phraseanet API to version 1.2 (see https://docs.phraseanet.com/3.7/en/Devel/API/Changelog.html#id1).
  - Phraseanet PHP SDK http://phraseanet-php-sdk.readthedocs.org/.

## 3.6.5 (2012-05-11)

  - BugFix : : Bridge buttons are not visible on some browsers.
  - Youtube and Dailymotion APIs updates.
  - Stories can now be deleted from the work zone.
  - Push and validation logs were missing.

## 3.6.4 (2012-04-30)

  - BugFix : DatePicker menus do not format date correctly.
  - BugFix : Dead records can remain in orders and may broke order window.

## 3.6.3 (2012-04-26)

  - BugFix : selection in webkit based browers.

## 3.6.2 (2012-04-19)

  - BugFix : : Users can be created by some pushers.
  - BugFix : : Collection owner can not disable watermark.
  - BugFix : : Basket element reorder issues.
  - BugFix : : Multiple order managers can not be added.
  - BugFix : : Basket editing can fail.
  - Remove original file extension for downloaded files.
  - Template is not applied when importing users from a file.
  - Document + XML hot folder import produces corrupted files.
  - Enhanced Push list view on small device.

## 3.6.1 (2012-03-27)

  - BugFix : upgrade from 3.5 versions with large datasets.

## 3.6.0 (2012-03-20)

  - Add a Vocabulary mapping to multivalued fields.
  - Redesign of Push and Feedback.
  - Add shareable users list for use with Push and Feedback.
  - WorkZone sidebar redesign.
  - Add an `archive` flag to baskets.
  - Add a basket browser to browse archived baskets.
  - New API 1.1, not compliant with 1.0, see release note v3.6 https://docs.phraseanet.com/3.6/en/Admin/Upgrade/3.6.html.
