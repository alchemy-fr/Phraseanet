<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use \Alchemy\Phrasea\Core\Service\Builder,
    \Alchemy\Phrasea\Core;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Manager
{

  /**
   *
   * @var \SplFileObject
   */
  protected $cacheFile;
  protected $core;

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration\Parser
   */
  protected $parser;

  /**
   *
   * @var array
   */
  protected $registry = array();

  public function __construct(Core $core, \SplFileObject $file)
  {
    $this->cacheFile = $file;
    $this->parser = new \Symfony\Component\Yaml\Yaml();
    $this->core = $core;

    $this->registry = $this->parser->parse($file);
  }

  protected function exists($name)
  {
    return isset($this->registry[$name]);
  }

  public function flushAll()
  {
    foreach ($this->registry as $cacheKey => $service_name)
    {
      $this->get($cacheKey, $service_name)->getDriver()->deleteAll();
    }

    return $this;
  }

  public function get($cacheKey, $service_name)
  {
    try
    {
      $configuration = $this->core->getConfiguration()->getService($service_name);
      $write = true;
    }
    catch (\Exception $e)
    {
      $configuration = new \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(
          array('type' => 'Cache\\ArrayCache')
      );
      $write = false;
    }

    $service = Builder::create($this->core, $service_name, $configuration);

    if ($this->hasChange($cacheKey, $service_name))
    {
      $service->getDriver()->deleteAll();
      if($write)
      {
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
      . $this->parser->dump($this->registry);

    file_put_contents($this->cacheFile->getPathname(), $datas);
  }

}

