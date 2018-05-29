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

class PhpRequirements extends RequirementCollection implements RequirementInterface
{
    public function __construct()
    {
        $this->setName('PHP');

        $this->addPhpIniRequirement(
            'date.timezone', true, false,
            'date.timezone setting must be set',
            'Set the "<strong>date.timezone</strong>" setting in php.ini<a href="#phpini">*</a> (like Europe/Paris).'
        );

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

        $this->addPhpIniRequirement('safe_mode', false, true);
        $this->addPhpIniRequirement('detect_unicode', false, true);
        $this->addPhpIniRequirement('file_uploads', true, true);
        $this->addPhpIniRequirement('session.cache_limiter', '');
        $this->addPhpIniRequirement('magic_quotes_gpc', false, true);
        $this->addPhpIniRequirement('magic_quotes_runtime', false, true);

        $this->addPhpIniRecommendation('short_open_tag', false);
        $this->addPhpIniRecommendation('register_globals', false, true);
        $this->addPhpIniRecommendation('session.auto_start', false);
        $this->addPhpIniRecommendation('display_errors', false, true);
        $this->addPhpIniRecommendation('display_startup_errors', false, true);
        $this->addPhpIniRecommendation('allow_url_fopen', true, true);
        $this->addPhpIniRecommendation('session.hash_bits_per_character', '6', true, 'session.hash_bits_per_character should be at least 6', 'Set session.hash_bits_per_character to 6 in php.ini');
        $this->addPhpIniRecommendation('session.hash_function', true, true);
        $this->addPhpIniRecommendation('session.use_only_cookies', true, true);
        $this->addPhpIniRecommendation('session.use_cookies', true, true);

        $this->addPhpIniRecommendation('session.cookie_http_only', true, true);
        $this->addPhpIniRecommendation('session.cookie_secure', true, true, 'session.cookie_secure should be enabled in php.ini, but only if you use HTTPS');
    }
}
