<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\RequirementCollection;
use Alchemy\Phrasea\Application as PhraseaApplication;

class LocalesRequirements extends RequirementCollection implements RequirementInterface
{
    public function __construct($locale = 'en_GB')
    {
        $this->setName('Locales');

        $this->addRequirement(
            class_exists('Locale'),
            'intl extension should be available',
            'Install and enable the <strong>intl</strong> extension (used for validators).'
        );

        if (function_exists('_')) {
            foreach (PhraseaApplication::getAvailableLanguages() as $code => $language_name) {
                \phrasea::use_i18n($code, 'test');

                $this->addRecommendation(
                    'test' === _('test::test'),
                    sprintf('Locale %s (%s) should be supported', $language_name, $code),
                    'Install support for locale <strong>' . $code . '</strong> (' . $language_name . ').'
                );

                \phrasea::use_i18n($locale);
            }
        }

        if (class_exists('Collator')) {
            $this->addRecommendation(
                null !== new \Collator('fr_FR'),
                'intl extension should be correctly configured',
                'The intl extension does not behave properly. This problem is typical on PHP 5.3.X x64 WIN builds.'
            );
        }

        if (class_exists('Locale')) {
            if (defined('INTL_ICU_VERSION')) {
                $version = INTL_ICU_VERSION;
            } else {
                $reflector = new \ReflectionExtension('intl');

                ob_start();
                $reflector->info();
                $output = strip_tags(ob_get_clean());

                preg_match('/^ICU version +(?:=> )?(.*)$/m', $output, $matches);
                $version = $matches[1];
            }

            $this->addRecommendation(
                version_compare($version, '4.0', '>='),
                'intl ICU version should be at least 4+',
                'Upgrade your <strong>intl</strong> extension with a newer ICU version (4+).'
            );
        }
    }
}
