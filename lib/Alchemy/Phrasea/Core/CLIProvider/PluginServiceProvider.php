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

use Alchemy\Phrasea\Plugin\Management\PluginsExplorer;
use Alchemy\Phrasea\Plugin\Management\ComposerInstaller;
use Alchemy\Phrasea\Plugin\Importer\Importer;
use Alchemy\Phrasea\Plugin\Importer\ImportStrategy;
use Alchemy\Phrasea\Plugin\Importer\FolderImporter;
use Alchemy\Phrasea\Plugin\Management\AutoloaderGenerator;
use Alchemy\Phrasea\Plugin\Management\AssetsManager;
use Symfony\Component\Process\PhpExecutableFinder;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PluginServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['plugins.import-strategy'] = $app->share(function (Application $app) {
            return new ImportStrategy();
        });

        $app['plugins.autoloader-generator'] = $app->share(function (Application $app) {
            return new AutoloaderGenerator($app['plugin.path']);
        });

        $app['plugins.assets-manager'] = $app->share(function (Application $app) {
            return new AssetsManager($app['filesystem'], $app['plugin.path'], $app['root.path']);
        });

        $app['plugins.composer-installer'] = $app->share(function (Application $app) {
            $phpBinary = $app['conf']->get(['main', 'binaries', 'php_binary'], null);

            if (!is_executable($phpBinary)) {
                $finder = new PhpExecutableFinder();
                $phpBinary = $finder->find();
            }

            return new ComposerInstaller($app['composer-setup'], $app['plugin.path'], $phpBinary);
        });
        $app['plugins.explorer'] = $app->share(function (Application $app) {
            return new PluginsExplorer($app['plugin.path']);
        });

        $app['plugins.importer'] = $app->share(function (Application $app) {
            return new Importer($app['plugins.import-strategy'], [
                'plugins.importer.folder-importer' => $app['plugins.importer.folder-importer'],
            ]);
        });

        $app['plugins.importer.folder-importer'] = $app->share(function (Application $app) {
           return new FolderImporter($app['filesystem']);
        });
    }

    public function boot(Application $app)
    {
    }
}
