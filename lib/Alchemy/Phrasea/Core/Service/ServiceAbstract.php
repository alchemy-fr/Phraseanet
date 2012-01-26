<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service;

use Alchemy\Phrasea\Core;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class ServiceAbstract
{

  protected $name;
  protected $options;
  protected $configuration;

  private $dependencies;

  public function __construct($name, Array $options, Array $dependencies)
  {
    $this->name = $name;
    $this->options = $options;
    $this->dependencies = $dependencies;

    $spec = new Core\Configuration\Application();
    $parser = new Core\Configuration\Parser\Yaml();
    $handler = new Core\Configuration\Handler($spec, $parser);

    $this->configuration = new Core\Configuration($handler);
  }

  public function getDependency($name)
  {
    if(!array_key_exists($name, $this->dependencies))
    {
      throw new \Exception(sprintf("Unknow dependency %s for %s service ", $name, $this->name));
    }

    return $this->dependencies[$name];
  }


  /**
   *
   * @return Array
   */
  public function getDependencies()
  {
    return $this->dependencies;
  }


  /**
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   *
   * @return Array
   */
  public function getOptions()
  {
    return $this->options;
  }

  /**
   *
   * @return string
   */
  public function getVersion()
  {
    return '';
  }

}
