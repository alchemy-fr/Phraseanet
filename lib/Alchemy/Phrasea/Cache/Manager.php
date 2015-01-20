<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Monolog\Logger;

class Manager
{
    private $file;
    /** @var Compiler */
    private $compiler;
    private $registry = array();
    private $drivers = array();
    /** @var Logger */
    private $logger;
    /** @var Factory */
    private $factory;

    public function __construct(Compiler $compiler, $file, Logger $logger, Factory $factory)
    {
        $this->file = $file;
        $this->compiler = $compiler;
        $this->logger = $logger;
        $this->factory = $factory;

        if (!is_file($file)) {
            $this->registry = array();
            $this->save();
        } else {
            $this->registry = require $file;
        }
    }

    /**
     * Flushes all registered cache
     *
     * @return Manager
     */
    public function flushAll()
    {
        foreach ($this->drivers as $driver) {
            $driver->flushAll();
        }

        $this->registry = array();
        $this->save();

        return $this;
    }

    /**
     * @param string $label
     * @param string $name
     * @param array  $options
     *
     * @return Cache
     */
    public function factory($label, $name, $options)
    {
        if ($this->isAlreadyRegistered($name, $label) && $this->isAlreadyLoaded($label)) {
            return $this->drivers[$label];
        }

        try {
            $cache = $this->factory->create($name, $options);
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage());
            $cache = $this->factory->create('array', array());
        }

        if (isset($options['namespace']) && is_string($options['namespace'])) {
            $cache->setNamespace($options['namespace']);
        } else {
            $cache->setNamespace(md5(gethostname().'-'.__DIR__));
        }

        $this->drivers[$label] = $cache;

        if (!$this->isAlreadyRegistered($name, $label)) {
            $this->register($name, $label);
            $cache->flushAll();
        }

        return $cache;
    }

    private function register($name, $label)
    {
        $this->registry[$label] = $name;
        $this->save();
    }

    private function isAlreadyRegistered($name, $label)
    {
        return isset($this->registry[$label]) && $name === $this->registry[$label];
    }

    private function isAlreadyLoaded($label)
    {
        return isset($this->drivers[$label]);
    }

    private function save()
    {
        $date = new \DateTime();
        $data = $this->compiler->compile($this->registry)
            . "\n// Last Update on ".$date->format(DATE_ISO8601)." \n";

        file_put_contents($this->file, $data);
    }
}
