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
class ServiceAbstract
{

  protected $name;
  protected $options;
  protected $configuration;

  public function __construct($name, Array $options)
  {
    $this->name = $name;
    $this->options = $options;

    $spec = new Core\Configuration\Application();
    $parser = new Core\Configuration\Parser\Yaml();
    $handler = new Core\Configuration\Handler($spec, $parser);

    $this->configuration = new Core\Configuration($handler);
  }

  /**
   *
   * @return Core\Configuration 
   */
  protected function getConfiguration()
  {
    return $this->configuration;
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

  /**
   *
   * @param type $serviceName
   * @param type $serviceScope
   * @return ServiceInterface
   */
  protected function findService($serviceName, $serviceScope, ParameterBag $configuration, $namespace = null)
  {
    
    return Core\ServiceBuilder::build(
                      $serviceName
                    , $serviceScope
                    , $configuration
                    , $namespace
    );
  }
}