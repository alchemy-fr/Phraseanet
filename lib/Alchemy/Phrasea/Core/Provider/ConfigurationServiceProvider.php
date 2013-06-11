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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigurationServiceProvider implements ServiceProviderInterface
{

    public function register(SilexApplication $app)
    {
        $app['phraseanet.configuration.yaml-parser'] = $app->share(function (SilexApplication $app) {
            return new Yaml();
        });
        $app['phraseanet.configuration.compiler'] = $app->share(function (SilexApplication $app) {
            return new Compiler();
        });
        $app['phraseanet.configuration.config-path'] = $app['root.path'] . '/config/configuration.yml';
        $app['phraseanet.configuration.config-compiled-path'] = $app['root.path'] . '/tmp/configuration-compiled.php';

        $app['phraseanet.configuration'] = $app->share(function(SilexApplication $app) {
            return new Configuration(
                $app['phraseanet.configuration.yaml-parser'],
                $app['phraseanet.configuration.compiler'],
                $app['phraseanet.configuration.config-path'],
                $app['phraseanet.configuration.config-compiled-path'],
                $app['debug']
            );
        });
    }

    public function boot(SilexApplication $app)
    {
    }
}
