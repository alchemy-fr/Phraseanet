<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Silex\Application;
use Silex\ServiceProviderInterface;

class LocaleServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['locale'] = $app->share(function (Application $app) {
            return $app['phraseanet.registry']->get('GV_default_lng', 'en_GB');
        });

        $app['locale.I18n'] = function (Application $app) {
            $data = explode('_', $app['locale']);

            return $data[0];
        };

        $app['locale.l10n'] = function (Application $app) {
            $data = explode('_', $app['locale']);

            return $data[1];
        };

        $app['locales.available'] = function (Application $app) {
            $availableLanguages = PhraseaApplication::getAvailableLanguages();

            if ($app['phraseanet.configuration']->isSetup()
                && isset($app['phraseanet.configuration']['main']['languages'])
                && !empty($app['phraseanet.configuration']['main']['languages'])) {
                $languages = $app['phraseanet.configuration']['main']['languages'];
                $enabledLanguages = $availableLanguages;

                foreach ($enabledLanguages as $code => $language) {
                    if (in_array($code, $languages)) {
                        continue;
                    }
                    $data = explode('_', $code);
                    if (in_array($data[0], $languages)) {
                        continue;
                    }
                    unset($enabledLanguages[$code]);
                }

                if (0 === count($enabledLanguages)) {
                    $app['monolog']->error('Wrong language configuration, no language activated');

                    return $availableLanguages;
                }

                return $enabledLanguages;
            } else {
                return $availableLanguages;
            }
        };

        $app['locales.mapping'] = function (Application $app) {
            $codes = array();
            foreach ($app['locales.available'] as $code => $language) {
                $data = explode('_', $code);
                $codes[$data[0]] = $code;
            }

            return $codes;
        };

        $app['locales.I18n.available'] = $app->share(function (Application $app) {
            $locales = array();

            foreach ($app['locales.available'] as $code => $language) {
                $data = explode('_', $code);
                $locales[$data[0]] = $language;
            }

            return $locales;
        });
    }

    public function boot(Application $app)
    {
    }
}
