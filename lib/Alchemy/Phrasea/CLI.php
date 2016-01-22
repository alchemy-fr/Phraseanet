<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea;

use Alchemy\Phrasea\Command\CommandInterface;
use Alchemy\Phrasea\Core\CLIProvider\TranslationExtractorServiceProvider;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeSubscriber;
use Alchemy\Phrasea\Core\PhraseaCLIExceptionHandler;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\Console;
use Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider;
use Alchemy\Phrasea\Core\CLIProvider\ComposerSetupServiceProvider;
use Alchemy\Phrasea\Core\CLIProvider\DoctrineMigrationServiceProvider;
use Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider;
use Alchemy\Phrasea\Core\CLIProvider\SignalHandlerServiceProvider;
use Alchemy\Phrasea\Core\CLIProvider\TaskManagerServiceProvider;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Phraseanet Command Line Application
 *
 * Largely inspired by Cilex
 * @see https://github.com/Cilex/Cilex
 */
class CLI extends Application
{
    /**
     * Registers the autoloader and necessary components.
     *
     * @param string      $name        Name for this application.
     * @param string|null $version     Version number for this application.
     * @param string|null $environment The environment.
     */
    public function __construct($name, $version = null, $environment = self::ENV_PROD)
    {
        parent::__construct($environment);

        $this['session.test'] = true;

        $this['console'] = $this->share(function () use ($name, $version, $environment) {
            $console = new Console\Application($name, $version);

            $console->getDefinition()->addOption(new InputOption(
                '--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $environment
            ));

            return $console;
        });

        $this['dispatcher'] = $this->share(
            $this->extend('dispatcher', function (EventDispatcher $dispatcher, Application $app) {
                $dispatcher->addListener('phraseanet.notification.sent', function () use ($app) {
                    $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']);
                });
                $dispatcher->addSubscriber(new BridgeSubscriber($app));

                return $dispatcher;
            })
        );

        $this->register(new PluginServiceProvider());
        $this->register(new ComposerSetupServiceProvider());
        $this->register(new CLIDriversServiceProvider());
        $this->register(new SignalHandlerServiceProvider());
        $this->register(new TaskManagerServiceProvider());
        $this->register(new TranslationExtractorServiceProvider());
        $this->register(new DoctrineMigrationServiceProvider());

        $this->bindRoutes();

        error_reporting(-1);
        ErrorHandler::register();
        PhraseaCLIExceptionHandler::register();
    }

    /**
     * Executes this application.
     *
     * @param bool $interactive runs in an interactive shell if true.
     */
    public function runCLI($interactive = false)
    {
        $this->boot();

        $app = $this['console'];
        if ($interactive) {
            $app = new Console\Shell($app);
        }

        $app->run();
    }

    public function boot()
    {
        parent::boot();

        $this['console']->setDispatcher($this['dispatcher']);
    }

    public function run(\Symfony\Component\HttpFoundation\Request $request = null)
    {
        if (null !== $request) {
            throw new RuntimeException('Phraseanet Konsole can not run Http Requests.');
        }

        $this->runCLI();
    }

    /**
     * Adds a command object.
     *
     * If a command with the same name already exists, it will be overridden.
     *
     * @param CommandInterface $command A Command object
     */
    public function command(CommandInterface $command)
    {
        $command->setContainer($this);
        $this['console']->add($command);
    }

    /**
     * {@inheritdoc}
     */
    public function loadPlugins()
    {
        parent::loadPlugins();

        call_user_func(function ($cli) {
            if (file_exists($cli['plugin.path'] . '/commands.php')) {
                require $cli['plugin.path'] . '/commands.php';
            }
        }, $this);
    }
}
