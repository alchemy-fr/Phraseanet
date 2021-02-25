<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\RequirementCollection;

class SystemRequirements extends RequirementCollection implements RequirementInterface
{
    const REQUIRED_PHP_VERSION = '5.5.0';

    public function __construct()
    {
        $installedPhpVersion = phpversion();

        $baseDir = realpath(__DIR__ . '/../../../../../');

        $this->setName('System');

        $this->addRequirement(
            version_compare($installedPhpVersion, self::REQUIRED_PHP_VERSION, '>='),
            sprintf('PHP version must be at least %s (%s installed)', self::REQUIRED_PHP_VERSION, $installedPhpVersion),
            sprintf('You are running PHP version "<strong>%s</strong>", but Phraseanet needs at least PHP "<strong>%s</strong>" to run.
                Before using Phraseanet, upgrade your PHP installation, preferably to the latest version.',
                $installedPhpVersion, self::REQUIRED_PHP_VERSION),
            sprintf('Install PHP %s or newer (installed version is %s)', self::REQUIRED_PHP_VERSION, $installedPhpVersion)
        );

        $this->addRequirement(
            is_dir($baseDir.'/vendor/composer'),
            'Vendor libraries must be installed',
            'Vendor libraries are missing. Install composer following instructions from <a href="http://getcomposer.org/">http://getcomposer.org/</a>. ' .
                'Then run "<strong>php composer.phar install</strong>" to install them.'
        );

        $this->addPhpIniRequirement(
            'date.timezone', true, false,
            'date.timezone setting must be set',
            'Set the "<strong>date.timezone</strong>" setting in php.ini<a href="#phpini">*</a> (like Europe/Paris).'
        );

        if (version_compare($installedPhpVersion, self::REQUIRED_PHP_VERSION, '>=')) {
            $timezones = [];
            foreach (\DateTimeZone::listAbbreviations() as $abbreviations) {
                foreach ($abbreviations as $abbreviation) {
                    $timezones[$abbreviation['timezone_id']] = true;
                }
            }

            $this->addRequirement(
                isset($timezones[date_default_timezone_get()]),
                sprintf('Configured default timezone "%s" must be supported by your installation of PHP', date_default_timezone_get()),
                'Your default timezone is not supported by PHP. Check for typos in your <strong>php.ini</strong> file and have a look at the list of deprecated timezones at <a href="http://php.net/manual/en/timezones.others.php">http://php.net/manual/en/timezones.others.php</a>.'
            );
        }

        $this->addRequirement(
            function_exists('json_encode'),
            'json_encode() must be available',
            'Install and enable the <strong>JSON</strong> extension.'
        );

        $this->addRequirement(
            function_exists('session_start'),
            'session_start() must be available',
            'Install and enable the <strong>session</strong> extension.'
        );

        $this->addRequirement(
            function_exists('ctype_alpha'),
            'ctype_alpha() must be available',
            'Install and enable the <strong>ctype</strong> extension.'
        );

        $this->addRequirement(
            function_exists('token_get_all'),
            'token_get_all() must be available',
            'Install and enable the <strong>Tokenizer</strong> extension.'
        );

        $this->addRequirement(
            function_exists('simplexml_import_dom'),
            'simplexml_import_dom() must be available',
            'Install and enable the <strong>SimpleXML</strong> extension.'
        );

        if (function_exists('apc_store') && ini_get('apc.enabled')) {
            if (version_compare($installedPhpVersion, '5.4.0', '>=')) {
                $this->addRequirement(
                    version_compare(phpversion('apc'), '3.1.13', '>='),
                    'APC version must be at least 3.1.13 when using PHP 5.4',
                    'Upgrade your <strong>APC</strong> extension (3.1.13+).'
                );
            } else {
                $this->addRequirement(
                    version_compare(phpversion('apc'), '3.0.17', '>='),
                    'APC version must be at least 3.0.17',
                    'Upgrade your <strong>APC</strong> extension (3.0.17+).'
                );
            }
        }

        $this->addPhpIniRequirement('detect_unicode', false);

        if (extension_loaded('suhosin')) {
            $this->addPhpIniRequirement(
                'suhosin.executor.include.whitelist',
                create_function('$cfgValue', 'return false !== stripos($cfgValue, "phar");'),
                false,
                'suhosin.executor.include.whitelist must be configured correctly in php.ini',
                'Add "<strong>phar</strong>" to <strong>suhosin.executor.include.whitelist</strong> in php.ini<a href="#phpini">*</a>.'
            );
        }

        if (extension_loaded('xdebug')) {
            $this->addPhpIniRequirement(
                'xdebug.show_exception_trace', false, true
            );

            $this->addPhpIniRequirement(
                'xdebug.scream', false, true
            );

            $this->addPhpIniRecommendation(
                'xdebug.max_nesting_level',
                create_function('$cfgValue', 'return $cfgValue > 100;'),
                true,
                'xdebug.max_nesting_level should be above 100 in php.ini',
                'Set "<strong>xdebug.max_nesting_level</strong>" to e.g. "<strong>250</strong>" in php.ini<a href="#phpini">*</a> to stop Xdebug\'s infinite recursion protection erroneously throwing a fatal error in your project.'
            );
        }

        $pcreVersion = defined('PCRE_VERSION') ? (float) PCRE_VERSION : null;

        $this->addRequirement(
            null !== $pcreVersion,
            'PCRE extension must be available',
            'Install the <strong>PCRE</strong> extension (version 8.0+).'
        );

        $this->addRecommendation(
            version_compare($installedPhpVersion, '5.4.0', '!='),
            'You should not use PHP 5.4.0 due to the PHP bug #61453',
            'Your project might not work properly due to the PHP bug #61453 ("Cannot dump definitions which have method calls"). Install PHP 5.4.1 or newer.'
        );

        if (null !== $pcreVersion) {
            $this->addRecommendation(
                $pcreVersion >= 8.0,
                sprintf('PCRE extension should be at least version 8.0 (%s installed)', $pcreVersion),
                '<strong>PCRE 8.0+</strong> is preconfigured in PHP since 5.3.2 but you are using an outdated version of it. Phraseanet probably works anyway but it is recommended to upgrade your PCRE extension.'
            );
        }

        $this->addRequirement(
            class_exists('DomDocument'),
            'PHP-XML module should be installed',
            'Install and enable the <strong>PHP-XML</strong> module.'
        );

        $this->addRequirement(
            function_exists('mb_strlen'),
            'mb_strlen() should be available',
            'Install and enable the <strong>mbstring</strong> extension.'
        );

        $this->addRequirement(
            function_exists('iconv'),
            'iconv() should be available',
            'Install and enable the <strong>iconv</strong> extension.'
        );

        $this->addRequirement(
            function_exists('exif_read_data'),
            'exif extension is required',
            'Install and enable the <strong>exif</strong> extension to enable FTP exports.'
        );

        $this->addRequirement(
            function_exists('curl_init'),
            'curl extension is required',
            'Install and enable the <strong>curl</strong> extension.'
        );

        $this->addRequirement(
            function_exists('gd_info'),
            'gd extension is required',
            'Install and enable the <strong>gd</strong> extension.'
        );

        $this->addRequirement(
            function_exists('hash_hmac'),
            'hash extension is required',
            'Install and enable the <strong>hash</strong> extension.'
        );

        if ('cli' === php_sapi_name() && !defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addRecommendation(
                function_exists('pcntl_fork'),
                'pcntl extension is recommended in unix environments',
                'Install and enable the <strong>pcntl</strong> extension to enable process fork.'
            );
        }

        $this->addRequirement(
            function_exists('proc_open'),
            'proc_* functions are required',
            'Enable the <strong>proc_c*</strong> functions.'
        );

        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addRecommendation(
                function_exists('posix_uname'),
                'Posix extension is recommended for task manager',
                'Install and enable the <strong>posix</strong> extension to enable process fork.'
            );
        }

        $this->addRequirement(
            function_exists('socket_connect'),
            'Socket extension is required for task manager',
            'Install and enable the <strong>socket</strong> extension.'
        );

        $this->addRequirement(
            class_exists('ZipArchive'),
            'Zip extension is required for download',
            'Install and enable the <strong>zip</strong> extension.'
        );

        $this->addRequirement(
            extension_loaded('zmq'),
            'ZMQ extension is required.',
            'Install and enable the <strong>ZMQ</strong> extension.'
        );

        $this->addRecommendation(
            class_exists('Imagick') || class_exists('Gmagick'),
            'Imagick or Gmagick extension is strongly recommended for image processing',
            'Install and enable the <strong>gmagick</strong> or <strong>imagick</strong> extension.'
        );

        $this->addRecommendation(
            function_exists('finfo_open'),
            'Fileinfo extension is recommended',
            'Install and enable the <strong>fileinfo</strong> extension to enable file detection.'
        );

        $this->addRequirement(
            function_exists('utf8_decode'),
            'utf8_decode() should be available',
            'Install and enable the <strong>XML</strong> extension.'
        );

        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addRecommendation(
                function_exists('posix_isatty'),
                'posix_isatty() should be available',
                'Install and enable the <strong>php_posix</strong> extension (used to colorize the CLI output).'
            );
        }

        $this->addRecommendation(
            function_exists('ftp_fget'),
            'ftp extension is required for FTP export',
            'Install and enable the <strong>ftp</strong> extension to enable FTP exports.'
        );

        $accelerator =
            version_compare(phpversion(), '5.5.0', '>=')
            || (function_exists('apc_store') && ini_get('apc.enabled'))
            || function_exists('eaccelerator_put') && ini_get('eaccelerator.enable')
            || function_exists('xcache_set')
        ;

        $this->addRecommendation(
            $accelerator,
            'a PHP accelerator should be installed',
            'Install and enable a <strong>PHP accelerator</strong> like APC (highly recommended).'
        );

        $this->addPhpIniRecommendation('short_open_tag', false);

        $this->addPhpIniRecommendation('magic_quotes_gpc', false, true);

        $this->addPhpIniRecommendation('register_globals', false, true);

        $this->addPhpIniRecommendation('session.auto_start', false);

        $this->addRequirement(
            class_exists('PDO'),
            'PDO should be installed',
            'Install <strong>PDO</strong> (mandatory for Doctrine).'
        );

        if (class_exists('PDO')) {
            $drivers = \PDO::getAvailableDrivers();
            $this->addRequirement(
                in_array('mysql', $drivers),
                sprintf('PDO should have MySQL driver installed (currently available: %s)', count($drivers) ? implode(', ', $drivers) : 'none'),
                'Install <strong>PDO MySQL driver</strong>.'
            );
        }
    }
}
