<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class ContentNegociationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['negociator'] = $app->share(function ($app) {
            return new \Negotiation\Negotiator();
        });

        $app['format.negociator'] = $app->share(function ($app) {
            return new \Negotiation\FormatNegotiator();
        });

        $app['langage.negociator'] = $app->share(function ($app) {
            return new \Negotiation\LanguageNegotiator();
        });
    }

    public function boot(Application $app)
    {
    }
}
