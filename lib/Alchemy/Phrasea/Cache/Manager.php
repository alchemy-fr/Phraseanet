<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Alchemy\Phrasea\Core\Configuration\Compiler;
use Monolog\Logger;

class Manager
{
    private $file;
    /** @var Compiler */
    private $compiler;
    private $registry = [];
    private $drivers = [];
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

        try {
            if (!is_file($file)) {
                $this->registry = [];
                $this->save();
            } else {
                $this->registry = require $file;
                if (!is_array($this->registry)) {
                    $this->registry = [];
                }
            }
        } catch (\Throwable $e) {
            // if the file content is not an array and not parsable
            $this->registry = [];
        }
    }

    /**
     * Flushes all registered cache
     *
     * @param null| string $pattern
     *
     * @return Manager
     */
    public function flushAll($pattern = null)
    {
        foreach ($this->drivers as $driver) {
            if ($driver->getName() === 'redis' && !empty($pattern)) {
                $driver->removeByPattern($pattern);
            } else {
                $driver->flushAll();
            }
        }

        $this->registry = [];
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
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $cache = $this->factory->create('array', []);
        }

        if (isset($options['namespace']) && is_string($options['namespace'])) {
            $cache->setNamespace($options['namespace']);
        } else {
            $cache->setNamespace(md5(gethostname() . '-' . __DIR__));
        }

        $this->drivers[$label] = $cache;

        if (!$this->isAlreadyRegistered($name, $label)) {
            $this->register($name, $label);

            // by default we use redis cache
            // so only initiate the corresponding namespace after register
            if ($cache->getName() === 'redis') {
                $cache->removeByPattern($cache->getNamespace() . '*');
            } else {
                $cache->flushAll();
            }
        }

        return $cache;
    }

    /**
     * @param string $name
     * @param string $label
     */
    private function register($name, $label)
    {
        $this->registry[$label] = $name;
        $this->save();
    }

    /**
     * @param string $name
     * @param string $label
     */
    private function isAlreadyRegistered($name, $label)
    {
        return isset($this->registry[$label]) && $name === $this->registry[$label];
    }

    /**
     * @param string $label
     */
    private function isAlreadyLoaded($label)
    {
        return isset($this->drivers[$label]);
    }

    private function save()
    {
        $date = new \DateTime();
        $data = $this->compiler->compile($this->registry);
//            . "\n// Last Update on " . $date->format(DATE_ISO8601) . " \n";

        file_put_contents($this->file, $data);
    }
}
