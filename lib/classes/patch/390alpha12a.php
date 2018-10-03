<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Plugin\Plugin;
use Alchemy\Phrasea\Plugin\Exception\PluginValidationException;
use Symfony\Component\Finder\Finder;

class patch_390alpha12a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.12';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        foreach ($this->listPlugins($app) as $name => $plugin) {
            $app['conf']->set(['plugins', $name, 'enabled'], true);
        }
    }

    private function listPlugins(Application $app)
    {
        $finder = new Finder();
        $finder
            ->depth(0)
            ->in($app['plugin.path'])
            ->directories();

        $plugins = [];

        foreach ($finder as $pluginDir) {
            $manifest = $error = null;
            $name = $pluginDir->getBasename();

            try {
                $manifest = $app['plugins.plugins-validator']->validatePlugin((string) $pluginDir);
            } catch (PluginValidationException $e) {
                $error = $e;
            }

            $plugins[$name] = new Plugin($name, $manifest, $error);
        }

        return $plugins;
    }
}
