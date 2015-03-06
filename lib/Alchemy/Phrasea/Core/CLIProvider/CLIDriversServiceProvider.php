<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\CLIProvider;

use Alchemy\Phrasea\Command\Developer\Utils\BowerDriver;
use Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver;
use Alchemy\Phrasea\Command\Developer\Utils\GruntDriver;
use Alchemy\Phrasea\Command\Developer\Utils\RecessDriver;
use Alchemy\Phrasea\Command\Developer\Utils\UglifyJsDriver;
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

        $app['driver.bower'] = $app->share(function (Application $app) {
            $bowerBinary = $app['driver.binary-finder']('bower', 'bower_binary');

            if (null === $bowerBinary) {
                throw new RuntimeException('Unable to find bower executable.');
            }

            return BowerDriver::create(['bower.binaries' => $bowerBinary, 'timeout' => 300], $app['monolog']);
        });

        $app['driver.recess'] = $app->share(function (Application $app) {
            $recessBinary = $app['driver.binary-finder']('recess', 'recess_binary');

            if (null === $recessBinary) {
                throw new RuntimeException('Unable to find recess executable.');
            }

            return RecessDriver::create(['recess.binaries' => $recessBinary], $app['monolog']);
        });

        $app['driver.composer'] = $app->share(function (Application $app) {
            $composerBinary = $app['driver.binary-finder']('composer', 'composer_binary');

            if (null === $composerBinary) {
                throw new RuntimeException('Unable to find composer executable.');
            }

            return ComposerDriver::create(['composer.binaries' => $composerBinary, 'timeout' => 300], $app['monolog']);
        });

        $app['driver.uglifyjs'] = $app->share(function (Application $app) {
            $uglifyJsBinary = $app['driver.binary-finder']('uglifyjs', 'uglifyjs_binary');

            if (null === $uglifyJsBinary) {
                throw new RuntimeException('Unable to find uglifyJs executable.');
            }

            return UglifyJsDriver::create(['uglifyjs.binaries' => $uglifyJsBinary], $app['monolog']);
        });

        $app['driver.grunt'] = $app->share(function (Application $app) {
            $gruntBinary = $app['driver.binary-finder']('grunt', 'grunt_binary');

            if (null === $gruntBinary) {
                throw new RuntimeException('Unable to find grunt executable.');
            }

            return GruntDriver::create(['grunt.binaries' => $gruntBinary], $app['monolog']);
        });
    }

    public function boot(Application $app)
    {
    }
}
