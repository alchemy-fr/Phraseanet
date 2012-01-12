<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ServiceBuilder
{
  const LOG = 'log';
  const TEMPLATE_ENGINE = 'template_engine';
  const ORM = 'orm';
  const CACHE = 'cache';
  
  protected static $scopes = array(
      self::ORM, self::TEMPLATE_ENGINE, self::LOG, self::CACHE
  );

  protected static $typeToService = array(
      'echo' => 'normal', 'array' => 'tab' 
  );
  
  protected static $optionsNotMandatory = array(
      'twig', 'apc', 'xcache', 'memcache', 'array', 'echo'
  );
  
  public static function build($serviceName, $serviceScope, ParameterBag $configuration, $namespace = null)
  {
    $serviceType = $configuration->get("type");
    
    if(!in_array($serviceType, self::$optionsNotMandatory))
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
    
    if (!in_array($serviceScope, self::$scopes))
    {
      throw new \Exception(sprintf("Unknow service scope of type %s", $serviceScope));
    }

    $composedScope = explode("_", $serviceScope);

    if (count($composedScope) > 1)
    {
      $scope = "";
      foreach ($composedScope as $word)
      {
        $scope .= ucfirst($word);
      }
      $serviceScope = $scope;
    }

    if (is_string($namespace))
    {
      $serviceScope = sprintf("%s\%s", ucfirst($serviceScope), ucfirst($namespace));
    }
    
    if(array_key_exists($serviceType, self::$typeToService))
    {
      $serviceType = self::$typeToService[$serviceType];
    }
    
    $className = sprintf(
            "\Alchemy\Phrasea\Core\Service\%s\%s"
            , ucfirst($serviceScope)
            , ucfirst($serviceType)
    );

    if (class_exists($className))
    {
      return new $className($serviceName, $options);
    }
    else
    {
      throw new \Exception(sprintf(
                      'Unknow service %s for %s scopes looked for classname %s'
                      , str_replace('/', '_', $serviceType)
                      , $serviceScope
                      , $className)
      );
    }
  }

}