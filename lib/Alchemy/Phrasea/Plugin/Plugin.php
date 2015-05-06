<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Plugin;

use Silex\Application;
use Symfony\Component\Console\Command\Command;

class Plugin
{
    /** @var string */
    private $name;
    /** @var string */
    private $prettyName;
    /** @var array */
    protected $configuration;

    public function __construct($name, array $configuration)
    {
        $this->prettyName = $name;
        $this->name = strtolower($name);
        $this->configuration = $configuration;
    }

    final public function getName()
    {
        return $this->name;
    }

    final public function getPrettyName()
    {
        return $this->prettyName;
    }

    /**
     * Get path to assets to be installed
     *
     * @return string
     */
    public function getAssetsPath()
    {
        return null;
    }

    /**
     * Get commands to be registered on CLI. Using their isEnabled() method.
     *
     * In case Command extends Alchemy\Phrasea\Command\Command, the Pimple Container will be injected
     * before checking for enabled.
     *
     * @return Command[]
     */
    public function getCommands()
    {
        return [];
    }

    /**
     * Use this to mount your routes.
     *
     * @param Application $app
     * @return void
     */
    public function bindWebRoutes(Application $app)
    {
        // No routes bound by default
    }

    /**
     * Use this to mount your api routes.
     *
     * @param Application $app
     * @return void
     */
    public function bindApiRoutes(Application $app)
    {
        // No routes bound by default
    }
}
