<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Parameter extends ParameterBag
{
  public function get($name, $default = null)
  {
    try
    {
      return parent::get($name);
    }
    catch(ParameterNotFoundException $e)
    {
      return $default;
    }
  }

}