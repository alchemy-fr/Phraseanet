<?php

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

class ProvidersCollection implements \Countable, \IteratorAggregate
{
    private $providers = array();

    public function getIterator()
    {
        return new \ArrayIterator($this->providers);
    }

    public function register(ProviderInterface $provider)
    {
        $this->providers[$provider->getId()] = $provider;
    }

    public function has($id)
    {
        return isset($this->providers[$id]);
    }

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
