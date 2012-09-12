<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Application;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class ConfigurationServiceProvider implements ServiceProviderInterface
{

    public function register(SilexApplication $app)
    {
        $app['phraseanet.configuration'] = $app->share(function(Application $app) {

            return Configuration::build(null, $app->getEnvironment());
        });
    }

    public function boot(SilexApplication $app)
    {
    }
}
