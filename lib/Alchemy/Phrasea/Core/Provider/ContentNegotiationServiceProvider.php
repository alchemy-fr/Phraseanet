<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Negotiation\FormatNegotiator;
use Negotiation\LanguageNegotiator;
use Negotiation\Negotiator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ContentNegotiationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['negociator'] = $app->share(function ($app) {
            return new Negotiator();
        });

        $app['format.negociator'] = $app->share(function ($app) {
            return new FormatNegotiator();
        });

        $app['langage.negociator'] = $app->share(function ($app) {
            return new LanguageNegotiator();
        });
    }

    public function boot(Application $app)
    {
    }
}
