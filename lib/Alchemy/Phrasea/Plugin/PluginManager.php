<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin;

use Alchemy\Phrasea\Plugin\Schema\PluginValidator;
use Alchemy\Phrasea\Plugin\Exception\PluginValidationException;
use Symfony\Component\Finder\Finder;

class PluginManager
{
    private $pluginDir;
    private $validator;

    public function __construct($pluginDir, PluginValidator $validator)
    {
        $this->pluginDir = $pluginDir;
        $this->validator = $validator;
    }

    /**
     * @return Plugin[] An array containing plugins
     */
    public function listPlugins()
    {
        $finder = new Finder();
        $finder
            ->depth(0)
            ->in($this->pluginDir)
            ->directories();

        $plugins = array();

        foreach ($finder as $pluginDir) {
            $manifest = $error = null;
            $name = $pluginDir->getBasename();

            try {
                $manifest = $this->validator->validatePlugin((string) $pluginDir);
            } catch (PluginValidationException $e) {
                $error = $e;
            }

            $plugins[$name] = new Plugin($name, $manifest, $error);
        }

        return $plugins;
    }

    public function hasPlugin($name)
    {
        $plugins = $this->listPlugins();

        return isset($plugins[$name]);
    }
}
