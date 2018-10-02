<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Hydration;

use Assert\Assertion;

class IdentityMap implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var object[]
     */
    private $entities;

    /**
     * @var Hydrator
     */
    private $hydrator;

    /**
     * @var object
     */
    private $prototype;

    /**
     * @param Hydrator $hydrator
     * @param object $prototype A clonable prototype of objects in idMap
     */
    public function __construct(Hydrator $hydrator, $prototype)
    {
        $this->hydrator = $hydrator;
        $this->prototype = $prototype;
    }

    /**
     * @param string|int $index
     * @param array $data
     * @return object
     */
    public function hydrate($index, array $data)
    {
        if (!isset($this->entities[$index])) {
            $this->entities[$index] = clone $this->prototype;
        }

        $instance = $this->entities[$index];

        $this->hydrator->hydrate($instance, $data);

        return $instance;
    }

    /**
     * @param array[] $data
     * @return object[]
     */
    public function hydrateAll(array $data)
    {
        Assertion::allIsArray($data);

        $instances = [];

        foreach ($data as $index => $item) {
            $instances[$index] = $this->hydrate($index, $item);
        }

        return $instances;
    }

    public function clear()
    {
        $this->entities = [];
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->entities);
    }

    public function offsetExists($offset)
    {
        return isset($this->entities[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->entities[$offset];
    }

    public function offsetSet($offset, $value)
    {
        Assertion::notNull($offset);
        Assertion::isArray($value);

        $this->hydrate($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->entities[$offset]);
    }

    public function count()
    {
        return count($this->entities);
    }
}
