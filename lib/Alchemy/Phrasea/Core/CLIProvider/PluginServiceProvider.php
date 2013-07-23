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

use Alchemy\Phrasea\Plugin\Schema\ManifestValidator;
use Alchemy\Phrasea\Plugin\Management\PluginsExplorer;
use Alchemy\Phrasea\Plugin\Management\ComposerInstaller;
use Alchemy\Phrasea\Plugin\Schema\PluginValidator;
use Alchemy\Phrasea\Plugin\Importer\Importer;
use Alchemy\Phrasea\Plugin\Importer\ImportStrategy;
use Alchemy\Phrasea\Plugin\Importer\FolderImporter;
use Alchemy\Phrasea\Plugin\Management\AutoloaderGenerator;
use Alchemy\Phrasea\Plugin\Management\AssetsManager;
use JsonSchema\Validator as JsonValidator;
use Symfony\Component\Process\PhpExecutableFinder;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PluginServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['plugins.schema'] = realpath(__DIR__ . '/../../../../conf.d/plugin-schema.json');

        $app['plugins.json-validator'] = $app->share(function (Application $app) {
            return new JsonValidator();
        });

        $app['plugins.manifest-validator'] = $app->share(function (Application $app) {
            return ManifestValidator::create($app);
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

        $app['plugins.assets-manager'] = $app->share(function (Application $app) {
            return new AssetsManager($app['filesystem'], $app['plugins.directory'], $app['root.path']);
        });

        $app['plugins.composer-installer'] = $app->share(function (Application $app) {
            $binaries = $app['phraseanet.configuration']['binaries'];
            $phpBinary = isset($binaries['php_binary']) ? $binaries['php_binary'] : null;

            if (!is_executable($phpBinary)) {
                $finder = new PhpExecutableFinder();
                $phpBinary = $finder->find();
            }

            return new ComposerInstaller($app['composer-setup'], $app['plugins.directory'], $phpBinary);
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
        $app['twig'] = $app->share(
            $app->extend('twig', function($twig, Application $app){
                $function = new \Twig_SimpleFunction('plugin_asset', array('Alchemy\Phrasea\Plugin\Management\AssetsManager', 'twigPluginAsset'));
                $twig->addFunction($function);

                return $twig;
            })
        );
    }
}
