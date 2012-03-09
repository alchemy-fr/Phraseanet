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

use Doctrine\Common\Cache\ApcCache as DoctrineApc;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ApcCache extends DoctrineApc implements Cache
{

  public function isServer()
  {
    return false;
  }

  public function getStats()
  {
    return null;
  }

  public function get($key)
  {
    if (!$this->contains($key))
    {
      throw new Exception('Unable to retrieve the value');
    }

    return $this->fetch($key);
  }

  public function deleteMulti(array $array_keys)
  {
    foreach ($array_keys as $id)
    {
      $this->delete($id);
    }

    return $this;
  }

  public function flush()
  {
    return apc_clear_cache() && apc_clear_cache('user');
  }

}
