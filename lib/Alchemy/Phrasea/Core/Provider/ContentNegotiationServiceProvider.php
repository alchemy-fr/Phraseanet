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

use Negotiation\CharsetNegotiator;
use Negotiation\EncodingNegotiator;
use Negotiation\LanguageNegotiator;
use Negotiation\Negotiator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ContentNegotiationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['negotiator'] = $app->share(function () {
            return new Negotiator();
        });

        $app['charset.negotiator'] = $app->share(function () {
            return new CharsetNegotiator();
        });
        $app['encoding.negotiator'] = $app->share(function () {
            return new EncodingNegotiator();
        });
        $app['language.negotiator'] = $app->share(function () {
            return new LanguageNegotiator();
        });
    }

    public function boot(Application $app)
    {
    }
}
