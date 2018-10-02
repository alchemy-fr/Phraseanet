<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

class ProvidersCollection implements \Countable, \IteratorAggregate
{
    private $providers = [];

    public function getIterator()
    {
        return new \ArrayIterator($this->providers);
    }

    /**
     * @param ProviderInterface $provider
     */
    public function register(ProviderInterface $provider)
    {
        $this->providers[$provider->getId()] = $provider;
    }

    /**
     * @param $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->providers[$id]);
    }

    /**
     * @param $id
     * @return ProviderInterface
     */
    public function get($id)
    {
        if (!isset($this->providers[$id])) {
            throw new InvalidArgumentException(sprintf('Unable to find provider %s', $id));
        }

        return $this->providers[$id];
    }

    public function count()
    {
        return count($this->providers);
    }
}
