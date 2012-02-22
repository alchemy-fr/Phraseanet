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

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag,
    Alchemy\Phrasea\Core;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Builder
{

  public static function create(Core $core, $name, ParameterBag $configuration)
  {
    $classname = __NAMESPACE__ . '\\' . $configuration->get("type");

    if (!class_exists($classname))
    {
      throw new Exception\ServiceNotFound(sprintf('Service %s not found', $classname));
    }

    try
    {
      $options = $configuration->get("options");
    }
    catch (\Exception $e)
    {
      $options = array();
    }

    $mandatory = $classname::getMandatoryOptions();

    if ($mandatory !== array_intersect($mandatory, array_keys($options)))
    {
      throw new Exception\MissingParameters(
        sprintf(
          'Missing parameters %s'
          , implode(', ', array_diff($mandatory, array_keys($options)))
        )
      );
    }

    return new $classname($core, $name, $options);
  }

}
