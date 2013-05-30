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

use Alchemy\Phrasea\Plugin\Schema\ManifestValidator;
use Alchemy\Phrasea\Plugin\Management\PluginsExplorer;
use Alchemy\Phrasea\Plugin\Management\ComposerInstaller;
use Alchemy\Phrasea\Plugin\Schema\PluginValidator;
use Alchemy\Phrasea\Plugin\Importer\Importer;
use Alchemy\Phrasea\Plugin\Importer\ImportStrategy;
use Alchemy\Phrasea\Plugin\Importer\FolderImporter;
use Alchemy\Phrasea\Plugin\Management\AutoloaderGenerator;
use Guzzle\Http\Client as Guzzle;
use JsonSchema\Validator as JsonValidator;
use Symfony\Component\Process\ExecutableFinder;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PluginServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['plugins.directory'] = realpath(__DIR__ . '/../../../../../plugins');
        $app['plugins.schema'] = realpath(__DIR__ . '/../../../../conf.d/plugin-schema.json');

        $app['json-validator'] = $app->share(function (Application $app) {
            return new JsonValidator();
        });

        $app['plugins.manifest-validator'] = $app->share(function (Application $app) {
            return ManifestValidator::create($app['json-validator'], $app['plugins.schema']);
        });

        $app['plugins.plugins-validator'] = $app->share(function (Application $app) {
            return new PluginValidator($app['plugins.manifest-validator']);
        });

        $app['plugins.import-strategy'] = $app->share(function (Application $app) {
            return new ImportStrategy();
        });

        $app['plugins.autoloader-generator'] = $app->share(function (Application $app) {
            return new AutoloaderGenerator($app['plugins.directory']);
        });

        $app['plugins.guzzle'] = $app->share(function (Application $app) {
            return new Guzzle();
        });

        $app['plugins.composer-installer'] = $app->share(function (Application $app) {
            if (null === $phpBinary = $app['phraseanet.registry']->get('php_binary')) {
                $finder = new ExecutableFinder();
                $phpBinary = $finder->find('php');
            }

            return new ComposerInstaller($app['plugins.directory'], $app['plugins.guzzle'], $phpBinary);
        });
        $app['plugins.explorer'] = $app->share(function (Application $app) {
            return new PluginsExplorer($app['plugins.directory']);
        });

        $app['plugins.importer'] = $app->share(function (Application $app) {
            return new Importer($app['plugins.import-strategy'], array(
                'plugins.importer.folder-importer' => $app['plugins.importer.folder-importer'],
            ));
        });

        $app['plugins.importer.folder-importer'] = $app->share(function (Application $app) {
           return new FolderImporter($app['filesystem']);
        });
    }

    public function boot(Application $app)
    {
    }
}
