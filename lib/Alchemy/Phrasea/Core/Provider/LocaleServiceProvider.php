<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
            if (!$app['configuration.store']->isSetup()) {
                return 'en';
            }

            return $app['conf']->get(['languages', 'default'], 'en');
        });

        $app['locales.available'] = $app->share(function (Application $app) {
            $availableLanguages = PhraseaApplication::getAvailableLanguages();

            if ($app['configuration.store']->isSetup() && 0 < count((array) $app['conf']->get(['languages', 'available']))) {
                $languages = $app['conf']->get(['languages', 'available'], []);
                $enabledLanguages = $availableLanguages;

                foreach ($enabledLanguages as $code => $language) {
                    if (in_array($code, $languages)) {
                        continue;
                    }
                    unset($enabledLanguages[$code]);
                }

                if (0 === count($enabledLanguages)) {
                    $app['monolog']->error('Wrong language configuration, no language activated');

                    return $availableLanguages;
                }

                return $enabledLanguages;
            }

            return $availableLanguages;
        });
    }

    public function boot(Application $app)
    {
    }
}
