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

use Alchemy\Phrasea\Core\Version;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application as SilexApplication;

class PhraseaVersionServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['phraseanet.version'] = function (SilexApplication $app) {
            return new Version();
        };
    }
}
