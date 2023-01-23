<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Exception\RuntimeException;

class HostConfiguration implements ConfigurationInterface
{
    private $configuration;
    private $cache;
    private $host;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
        $this->cache = $configuration->isSetup() ? $this->configuration->getConfig() : [];
    }

    /**
     * Sets the host name to switch on.
     *
     * @param $host
     */
    public function setHost($host)
    {
        $this->host = trim(strtolower($host));

        if ('' === $this->host) {
            $this->merge();

            return;
        }

        if (!isset($this->cache['hosts-configuration'])) {
            $this->merge();

            return;
        }

        foreach ($this->cache['hosts-configuration'] as $hostConf) {
            if (!isset($hostConf['servername'])) {
                continue;
            }
            if ($this->match($this->host, $hostConf['servername'])) {
                $this->merge($hostConf);
                break;
            }
            if (!isset($hostConf['hosts'])) {
                continue;
            }
            foreach ((array) $hostConf['hosts'] as $hostname) {
                if ($this->match($this->host, $hostname)) {
                    $this->merge($hostConf);
                    break 2;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->cache[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->configuration[$offset] = $value;
        $this->cache = $this->configuration->getConfig();
        $this->setHost($this->host);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->cache[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->configuration->offsetUnset($offset);
        $this->cache = $this->configuration->getConfig();
        $this->setHost($this->host);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $this->configuration->initialize();
        $this->cache = $this->configuration->getConfig();
        $this->setHost($this->host);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->configuration->delete();
        $this->cache = [];
        $this->setHost($this->host);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isSetup()
    {
        return $this->configuration->isSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefault($name)
    {
        $this->configuration->setDefault($name);
        $this->cache = $this->configuration->getConfig();
        $this->setHost($this->host);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        if (empty($this->cache)) {
            throw new RuntimeException('Configuration is not set up.');
        }

        if ($this->configuration->getNoCompile()) {
            return $this->configuration->getConfig();
        }

        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config)
    {
        $this->configuration->setConfig($config);
        $this->cache = $this->configuration->getConfig();
        $this->setHost($this->host);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compileAndWrite()
    {
        $this->configuration->compileAndWrite();
        $this->cache = $this->configuration->getConfig();
        $this->setHost($this->host);
    }

    public function setNoCompile(bool $noCompile)
    {
        $this->configuration->setNoCompile($noCompile);
    }

    private function match($host, $hostname)
    {
        return $this->removeHostPrefix($host) === $this->removeHostPrefix($hostname);
    }

    private function removeHostPrefix($hostname)
    {
        $data = parse_url($hostname);

        if (!isset($data['host'])) {
            return $hostname;
        }

        if (!isset($data['path']) || '/' === $data['path']) {
            return strtolower(rtrim($data['host'], '/'));
        } else {
            return strtolower($data['host'].$data['path']);
        }
    }

    private function merge(array $subConf = [])
    {
        if (!$this->configuration->isSetup()) {
            $this->cache = [];

            return;
        }

        $config = $this->configuration->getConfig();

        if (empty($subConf)) {
            $this->cache = $config;

            return;
        }

        foreach (array_keys($subConf) as $property) {
            if (in_array($property, ['main', 'xsendfile', 'hosts-configuration', 'databoxes'])) {
                continue;
            }

            $data = isset($config[$property]) ? $config[$property] : (is_array($subConf[$property]) ? [] : null);
            if (is_array($data)) {
                $data = array_replace_recursive($data, $subConf[$property]);
            } else {
                $data = $subConf[$property];
            }
            $config[$property] = $data;
        }

        if (isset($subConf['databoxes'])) {
            $config['databoxes'] = $subConf['databoxes'];
        }

        $this->cache = $config;
    }
}
