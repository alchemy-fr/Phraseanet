<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\CLIProvider;

use Alchemy\Phrasea\Command\Developer\Utils\BowerDriver;
use Alchemy\Phrasea\Command\Developer\Utils\UglifyJsDriver;
use Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Process\ExecutableFinder;

class CLIDriversServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['executable-finder'] = $app->share(function () {
            return new ExecutableFinder();
        });

        $app['driver.bower'] = $app->share(function (Application $app) {
            if (isset($this->container['phraseanet.configuration']['binaries']['bower_binary'])) {
                $bowerBinary = $this->container['phraseanet.configuration']['binaries']['bower_binary'];
            } else {
                $bowerBinary = $app['executable-finder']->find('bower');
            }

            if (null === $bowerBinary) {
                throw new RuntimeException('Unable to find bower executable.');
            }

            return BowerDriver::create(array('bower.binaries' => $bowerBinary), $this->container['monolog']);
        });

        $app['driver.composer'] = $app->share(function (Application $app) {
            if (isset($this->container['phraseanet.configuration']['binaries']['composer_binary'])) {
                $composerBinary = $this->container['phraseanet.configuration']['binaries']['composer_binary'];
            } else {
                $composerBinary = $app['executable-finder']->find('composer');
            }

            if (null === $composerBinary) {
                throw new RuntimeException('Unable to find composer executable.');
            }

            return ComposerDriver::create(array('composer.binaries' => $composerBinary), $this->container['monolog']);
        });

        $app['driver.uglifyjs'] = $app->share(function (Application $app) {
            return UglifyJsDriver::create(array(), $this->container['monolog']);
        });
    }

    public function boot(Application $app)
    {
    }
}
