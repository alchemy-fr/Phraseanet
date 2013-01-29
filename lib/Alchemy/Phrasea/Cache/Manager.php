<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Service\Builder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Yaml;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Manager
{
    /**
     *
     * @var \SplFileInfo
     */
    protected $cacheFile;
    protected $app;

    /**
     *
     */
    protected $parser;

    /**
     *
     * @var array
     */
    protected $registry = array();

    public function __construct(Application $app, \SplFileInfo $file)
    {
        $this->cacheFile = $file;
        $this->parser = new Yaml();
        $this->app = $app;

        $this->registry = $this->parser->parse($file) ? : array();
    }

    protected function exists($name)
    {
        return isset($this->registry[$name]);
    }

    public function flushAll()
    {
        foreach ($this->registry as $cacheKey => $service_name) {
            $this->get($cacheKey, $service_name)->getDriver()->flushAll();
        }

        file_put_contents($this->cacheFile->getPathname(), '');

        return $this;
    }

    public function get($cacheKey, $service_name)
    {
        try {
            $configuration = $this->app['phraseanet.configuration']->getService($service_name);
            $service = Builder::create($this->app, $configuration);
            $driver = $service->getDriver();
            $write = true;
        } catch (\Exception $e) {
            $configuration = new ParameterBag(
                    array('type'   => 'Cache\\ArrayCache')
            );
            $service = Builder::create($this->app, $configuration);
            $driver = $service->getDriver();
            $write = false;
        }

        if ($this->hasChange($cacheKey, $service_name)) {
            $service->getDriver()->flushAll();
            if ($write) {
                $this->registry[$cacheKey] = $service_name;
                $this->save($cacheKey, $service_name);
            }
        }

        return $service;
    }

    protected function hasChange($name, $driver)
    {
        return $this->exists($name) ? $this->registry[$name] !== $driver : true;
    }

    protected function save($name, $driver)
    {
        $date = new \DateTime();

        $this->registry[$name] = $driver;

        $datas = sprintf("#LastUpdate: %s\n", $date->format(DATE_ISO8601))
            . $this->parser->dump($this->registry, 6);

        file_put_contents($this->cacheFile->getPathname(), $datas);
    }
}
