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
        $app['locale'] = $app->share(function(Application $app){
            return $app['phraseanet.registry']->get('GV_default_lng', 'en_GB');
        });

        $app['locale.I18n'] = function(Application $app){
            $data = explode('_', $app['locale']);

            return $data[0];
        };

        $app['locale.l10n'] = function(Application $app){
            $data = explode('_', $app['locale']);

            return $data[1];
        };

        $app['locales.available'] = function (Application $app) {
            return PhraseaApplication::getAvailableLanguages();
        };

        $app['locales.mapping'] = function (Application $app) {
            $codes = array();
            foreach (PhraseaApplication::getAvailableLanguages() as $code => $language) {
                $data = explode('_', $code);
                $codes[$data[0]] = $code;
            }

            return $codes;
        };

        $app['locales.I18n.available'] = $app->share(function (Application $app) {
            $locales = array();

            foreach (PhraseaApplication::getAvailableLanguages() as $code => $language) {
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
