<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Doctrine\Common\Cache\MemcachedCache as DoctrineMemcached;

class MemcachedCache extends DoctrineMemcached implements Cache
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'memcached';
    }

    /**
     * {@inheritdoc}
     */
    public function isOnline()
    {
        return (Boolean) $this->getMemcached()->getstats();
    }

    /**
     * {@inheritdoc}
     */
    public function isServer()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if ( ! $this->contains($key)) {
            throw new Exception('Unable to retrieve the value');
        }

        return $this->fetch($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return $this;
    }
}
