<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

class Configuration implements ConfigurationInterface
{
    const CONFIG_REF = '/../../../../../lib/conf.d/configuration.yml';

    private $cache;
    private $parser;
    private $compiler;
    private $config;
    private $compiled;
    private $autoReload;

    /**
     * @param Yaml     $yaml       The Yaml Parser
     * @param Compiler $compiler   The PHP Compiler
     * @param string   $config     The path to the yaml configuration path
     * @param string   $compiled   The path to the compiled configuration path
     * @param Boolean  $autoReload Whether to recompile configuration on any change (slow, useful in debug)
     */
    public function __construct(Yaml $yaml, Compiler $compiler, $config, $compiled, $autoReload)
    {
        $this->parser = $yaml;
        $this->compiler = $compiler;
        $this->config = $config;
        $this->compiled = $compiled;
        $this->autoReload = (Boolean) $autoReload;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $conf = $this->getConfig();

        return isset($conf[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $conf = $this->getConfig();
        $conf[$offset] = $value;

        $this->setConfig($conf);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $conf = $this->getConfig();

        return $conf[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $conf = $this->getConfig();
        unset($conf[$offset]);

        $this->setConfig($conf);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefault($name)
    {
        $defaultConfig = $this->loadDefaultConfiguration();

        if (!isset($defaultConfig[$name])) {
            throw new InvalidArgumentException(sprintf('%s is not a valid config name', $name));
        }

        $newConfig = $this->doSetDefault($this->getConfig(), $defaultConfig, func_get_args());

        return $this->setConfig($newConfig);
    }

    private function doSetDefault($newConfig, $default, array $keys)
    {
        $name = array_shift($keys);

        if (!isset($default[$name])) {
            throw new InvalidArgumentException(sprintf('%s is not a valid config name', $name));
        }

        if (count($keys) === 0) {
            $newConfig[$name] = $default[$name];
        } else {
            $newConfig[$name] = $this->doSetDefault($newConfig[$name], $default[$name], $keys);
        }

        return $newConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        if (!is_file($this->compiled) || ($this->isAutoReload() && !$this->isConfigFresh())) {
            if (!$this->isSetup()) {
                throw new RuntimeException('Configuration is not set up');
            }
            $this->writeCacheConfig($this->compiler->compile(
                $this->parser->parse($this->loadFile($this->config))
            ));
        }

        return $this->cache = require $this->compiled;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config)
    {
        $this->cache = $config;
        $this->dumpFile($this->config, $this->parser->dump($config, 7), 0660);
        $this->writeCacheConfig($this->compiler->compile($config));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compileAndWrite()
    {
        $this->cache = null;
        $this->writeCacheConfig($this->compiler->compile(
            $this->parser->parse($this->loadFile($this->config))
        ));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->cache = null;
        foreach ([
            $this->config,
            $this->compiled,
        ] as $file) {
            $this->eraseFile($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $this->delete();
        $this->dumpFile($this->config, $this->loadFile(__DIR__ . static::CONFIG_REF), 0660);

        // force rewrite
        $this->getConfig();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isSetup()
    {
        return file_exists($this->config);
    }

    private function isAutoReload()
    {
        return $this->autoReload;
    }

    private function loadDefaultConfiguration()
    {
        return $this->parser->parse($this->loadFile(__DIR__ . static::CONFIG_REF));
    }

    private function writeCacheConfig($content)
    {
        $this->dumpFile($this->compiled, $content, 0660);

        if(function_exists("opcache_invalidate")) {
            opcache_invalidate($this->compiled);
        }
    }

    private function isConfigFresh()
    {
        return @filemtime($this->config) <= @filemtime($this->compiled);
    }

    private function loadFile($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new RuntimeException(sprintf('Unable to read %s', $file));
        }

        return file_get_contents($file);
    }

    private function dumpFile($file, $content, $mod = 0600)
    {
        $tmpFile = tempnam(dirname($file), basename($file));

        if (false !== @file_put_contents($tmpFile, $content)) {
            // rename does not work on Win32 before 5.2.6
            if (@rename($tmpFile, $file)) {
                @chmod($file, $mod & ~umask());

                return;
            }
        }

        unlink($tmpFile);
        throw new RuntimeException(sprintf('Unable to write %s', $file));
    }

    private function eraseFile($file)
    {
        if (is_file($file)) {
            unlink($file);
        }
    }
}
