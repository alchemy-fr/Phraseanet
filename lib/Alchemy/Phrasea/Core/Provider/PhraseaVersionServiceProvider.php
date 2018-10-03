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
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class PhraseaVersionServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {
        $app['phraseanet.version'] = $app->share(function (SilexApplication $app) {
            return new Version();
        });
    }

    public function boot(SilexApplication $app)
    {
    }
}
