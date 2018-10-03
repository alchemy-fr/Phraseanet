<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\CLIProvider;

use Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver;
use Alchemy\Phrasea\Exception\RuntimeException;
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

        $app['driver.binary-finder'] = $app->protect(function ($name, $configName) use ($app) {
            $extraDirs = [];

            if (is_dir($app['root.path'] . '/node_modules')) {
                $extraDirs[] = $app['root.path'] . '/node_modules/.bin';
            }

            if (!$app['configuration.store']->isSetup()) {
                return $app['executable-finder']->find($name, null, $extraDirs);
            }

            if ($app['conf']->has(['main', 'binaries', $configName])) {
                return $app['conf']->get(['main', 'binaries', $configName]);
            }

            return $app['executable-finder']->find($name, null, $extraDirs);
        });

        $app['driver.composer'] = $app->share(function (Application $app) {
            $composerBinary = $app['driver.binary-finder']('composer', 'composer_binary');

            if (null === $composerBinary) {
                throw new RuntimeException('Unable to find composer executable.');
            }

            return ComposerDriver::create(['composer.binaries' => $composerBinary, 'timeout' => 300], $app['monolog']);
        });
    }

    public function boot(Application $app)
    {
    }
}
