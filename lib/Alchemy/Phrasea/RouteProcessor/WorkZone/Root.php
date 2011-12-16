<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\RouteProcessor\WorkZone;

use Alchemy\Phrasea\RouteProcessor;
use Alchemy\Phrasea\Helper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Root extends RouteProcessor\RouteAbstract
{

  public function getAllowedMethods()
  {
    return array('GET');
  }

  protected function get()
  {
    
  }

}
