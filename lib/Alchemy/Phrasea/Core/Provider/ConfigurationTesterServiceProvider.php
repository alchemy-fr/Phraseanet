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

use Alchemy\Phrasea\Setup\ConfigurationTester;
use Alchemy\Phrasea\Application;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class ConfigurationTesterServiceProvider implements ServiceProviderInterface
{

    public function register(SilexApplication $app)
    {
        $app['phraseanet.configuration-tester'] = $app->share(function(Application $app) {
            return new ConfigurationTester($app);
        });
    }

    public function boot(SilexApplication $app)
    {
    }
}
