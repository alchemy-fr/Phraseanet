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

use Alchemy\Phrasea\Command\Developer\Utils\ConstraintExtractor;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Gedmo\SoftDeleteable\Mapping\Driver\Annotation;
use JMS\TranslationBundle\Translation\ConfigBuilder;
use JMS\TranslationBundle\Translation\Dumper\SymfonyDumperAdapter;
use JMS\TranslationBundle\Translation\Dumper\XliffDumper;
use JMS\TranslationBundle\Translation\Extractor\File\DefaultPhpFileExtractor;
use JMS\TranslationBundle\Translation\Extractor\File\FormExtractor;
use JMS\TranslationBundle\Translation\Extractor\File\TwigFileExtractor;
use JMS\TranslationBundle\Translation\Extractor\File\ValidationExtractor;
use JMS\TranslationBundle\Translation\Extractor\FileExtractor;
use JMS\TranslationBundle\Translation\ExtractorManager;
use JMS\TranslationBundle\Translation\FileWriter;
use JMS\TranslationBundle\Translation\Loader\SymfonyLoaderAdapter;
use JMS\TranslationBundle\Translation\Loader\XliffLoader;
use JMS\TranslationBundle\Translation\LoaderManager;
use JMS\TranslationBundle\Translation\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Dumper\PoFileDumper;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Process\PhpExecutableFinder;

class TranslationExtractorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['translation-extractor.logger'] = $app->share(function (Application $app) {
            return $app['monolog'];
        });
        $app['translation-extractor.doc-parser'] = $app->share(function () {
            $parser =  new DocParser();
            $parser->addNamespace("JMS\TranslationBundle\Annotation");

            return $parser;
        });
        $app['translation-extractor.node-visitors'] = $app->share(function (Application $app) {
            return array(
                new ConstraintExtractor($app),
                new ValidationExtractor($app['validator']->getMetadataFactory()),
                new DefaultPhpFileExtractor($app['translation-extractor.doc-parser']),
                new TwigFileExtractor($app['twig']),
                new FormExtractor($app['translation-extractor.doc-parser']),
            );
        });
        $app['translation-extractor.file-extractor'] = $app->share(function (Application $app) {
            return new FileExtractor($app['twig'], $app['translation-extractor.logger'], $app['translation-extractor.node-visitors']);
        });
        $app['translation-extractor.extractor-manager'] = $app->share(function (Application $app) {
            return new ExtractorManager($app['translation-extractor.file-extractor'], $app['translation-extractor.logger']);
        });

        $app['translation-extractor.writer'] = $app->share(function (Application $app) {
            return new FileWriter($app['translation-extractor.writers']);
        });

        $app['translation-extractor.writers'] = $app->share(function () {
            return array(
                'po' => new SymfonyDumperAdapter(new PoFileDumper(), 'po'),
                'xliff' => new XliffDumper(),
            );
        });

        $app['translation-extractor.loader-manager'] = $app->share(function (Application $app) {
            return new LoaderManager($app['translation-extractor.loaders']);
        });
        $app['translation-extractor.loaders'] = $app->share(function () {
            return array(
                'po' => new SymfonyLoaderAdapter(new PoFileLoader()),
                'xliff' => new XliffLoader()
            );
        });

        $app['translation-extractor.updater'] = $app->share(function (Application $app) {
            AnnotationRegistry::registerAutoloadNamespace('JMS\TranslationBundle\Annotation', $app['root.path'].'/vendor/jms/translation-bundle');

            return new Updater($app['translation-extractor.loader-manager'], $app['translation-extractor.extractor-manager'], $app['translation-extractor.logger'], $app['translation-extractor.writer']);
        });
    }

    public function boot(Application $app)
    {
    }
}
