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

use Alchemy\Phrasea\Core\Service;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Cache extends AbstractBuilder
{
  protected static $optionsNotMandatory = array('apc', 'xcache', 'memcache', 'array', 'memcached', 'redis');

  public static function create($name, ParameterBag $configuration, Array $dependencies = array(), $namespace = null)
  {
    $type = $configuration->get("type");

    $options = parent::getServiceOptions($type, $configuration);

    $className = sprintf("\Alchemy\Phrasea\Core\Service\Cache\%sCache", ucfirst($type));

    if (class_exists($className))
    {
       return new $className($name, $options, $dependencies);
    }
    else
    {
      throw new \Exception(sprintf(
                      'Unknow service %s for cache scopes looked for classname %s'
                      , str_replace('/', '_', $type)
                      , $className)
      );
    }
  }

}
