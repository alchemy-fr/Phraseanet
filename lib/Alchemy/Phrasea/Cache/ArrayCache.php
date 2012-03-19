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

use Doctrine\Common\Cache\ArrayCache as DoctrineArray;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ArrayCache extends DoctrineArray implements Cache
{

  public function isServer()
  {

    return false;
  }

  public function get($id)
  {
    if (!$this->contains($id))
    {
      throw new Exception(sprintf('Unable to find key %s', $id));
    }

    return $this->fetch($id);
  }

  public function deleteMulti(array $array_keys)
  {
    foreach ($array_keys as $id)
    {
      $this->delete($id);
    }

    return;
  }

}
