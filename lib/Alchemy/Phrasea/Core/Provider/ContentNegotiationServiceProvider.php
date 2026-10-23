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
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ContentNegotiationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['negotiator'] = function () {
            return new Negotiator();
        };

        $app['charset.negotiator'] = function () {
            return new CharsetNegotiator();
        };
        $app['encoding.negotiator'] = function () {
            return new EncodingNegotiator();
        };
        $app['language.negotiator'] = function () {
            return new LanguageNegotiator();
        };
    }
}
