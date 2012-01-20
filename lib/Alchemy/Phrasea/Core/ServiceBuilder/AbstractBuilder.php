<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\ServiceBuilder;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class AbstractBuilder
{

  protected static $optionsNotMandatory = array();
  
  private $service;

  public function __construct($name, ParameterBag $configuration, Array $dependencies = array(), $namespace = null)
  {
    if (!is_string($name) && empty($name))
    {
      throw new \Exception(sprintf("Service name must be a string %s given", var_export($name, true)));
    }

    $this->service = static::create($name, $configuration, $dependencies, $namespace);
  }

  /**
   *
   * @return ServiceAbstract 
   */
  public function buildService()
  {
    return $this->service;
  }

  public static function create($name, ParameterBag $configuration, Array $dependencies = array(), $namespace = null)
  {
    throw new \Exception("Abstract factory does not create any concrete Service");
  }
  
  protected static function getServiceOptions($type, ParameterBag $configuration)
  {
    if(!in_array($type, static::$optionsNotMandatory))
    {
      $options = $configuration->get("options");
    }
    else
    {
      try
      {
        $options = $configuration->get("options");
      }
      catch(\Exception $e)
      {
        $options = array();
      }
    }
    return $options;
  }
  
}