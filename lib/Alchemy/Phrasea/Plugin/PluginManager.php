<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Plugin\Schema\PluginValidator;
use Alchemy\Phrasea\Plugin\Exception\PluginValidationException;

class PluginManager
{
    private $pluginDir;
    private $validator;
    private $conf;

    public function __construct($pluginDir, PluginValidator $validator, PropertyAccess $conf)
    {
        $this->pluginDir = $pluginDir;
        $this->validator = $validator;
        $this->conf = $conf;
    }

    /**
     * @return Plugin[] An array containing plugins
     */
    public function listPlugins()
    {
        $plugins = [];

        foreach ($this->conf->get('plugins') as $name => $config) {
            $manifest = $error = null;

            try {
                $manifest = $this->validator->validatePlugin($this->pluginDir.'/'.$name);
            } catch (PluginValidationException $e) {
                $error = $e;
            }

            $plugins[$name] = new Plugin($name, $manifest, $error);
        }

        return $plugins;
    }

    public function hasPlugin($name)
    {
        return array_key_exists($name, $this->conf->get('plugins'));
    }

    public function enable($name)
    {
        $this->conf->set(['plugins', $name, 'enabled'], true);

        return $this;
    }

    public function disable($name)
    {
        $this->conf->set(['plugins', $name, 'enabled'], false);

        return $this;
    }

    public function isEnabled($name)
    {
        if (!$this->hasPlugin($name)) {
            return false;
        }

        return $this->conf->get(['plugins', $name, 'enabled'], false);
    }
}
