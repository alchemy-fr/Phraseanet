<?php

namespace Alchemy\Tests\Phrasea;

use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;

class MockArrayConf implements ConfigurationInterface
{
    private $conf;

    public function __construct(array $conf)
    {
        $this->conf = $conf;
    }

    public function getConfig()
    {
        return $this->conf;
    }

    public function setConfig(array $config)
    {
        $this->conf = $config;
    }

    public function offsetGet($key)
    {
        return $this->conf[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->conf[$key] = $value;
    }

    public function offsetExists($key)
    {
        return isset($this->conf[$key]);
    }

    public function offsetUnset($key)
    {
        unset($this->conf[$key]);
    }

    public function setDefault($name)
    {
        throw new \Exception('not implemented');
    }

    public function initialize()
    {
        throw new \Exception('not implemented');
    }

    public function delete()
    {
        throw new \Exception('not implemented');
    }

    public function isSetup()
    {
        throw new \Exception('not implemented');
    }

    public function compileAndWrite()
    {
        throw new \Exception('not implemented');
    }
}
