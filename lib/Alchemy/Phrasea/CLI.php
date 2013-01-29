<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea;

use Symfony\Component\Console;

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
    public function __construct($name, $version = null, $environment = null)
    {
        parent::__construct($environment);

        $app = $this;

        $this['session.test'] = true;

        $this['console'] = $this->share(function () use ($name, $version) {
            return new Console\Application($name, $version);
        });

        $this['dispatcher']->addListener('phraseanet.notification.sent', function() use ($app) {
            $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']);
        });

        $this->bindRoutes();

        $data = parse_url($this['phraseanet.registry']->get('GV_ServerName'));

        if (isset($data['scheme'])) {
            $this['url_generator']->getContext()->setScheme($data['scheme']);
        }
        if (isset($data['host'])) {
            $this['url_generator']->getContext()->setHost($data['host']);
        }
    }

    /**
     * Executes this application.
     *
     * @param bool $interactive runs in an interactive shell if true.
     */
    public function runCLI($interactive = false)
    {
        $app = $this['console'];
        if ($interactive) {
            $app = new Console\Shell($app);
        }

        $app->run();
    }

    public function run(\Symfony\Component\HttpFoundation\Request $request = null)
    {
        $this->runCLI();
    }

    /**
     * Adds a command object.
     *
     * If a command with the same name already exists, it will be overridden.
     *
     * @param \Cilex\Command\Command $command A Command object
     */
    public function command(Command\Command $command)
    {
        $command->setContainer($this);
        $this['console']->add($command);
    }
}
