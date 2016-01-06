<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Feed;

use Alchemy\Phrasea\Model\Repositories\FeedEntryRepository;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;

class AggregateEntryCollection implements Collection, Selectable
{
    /** @var FeedEntryRepository */
    private $repository;
    /** @var FeedInterface[] */
    private $feeds;
    /** @var ArrayCollection|null */
    private $entries;

    /**
     * @param FeedEntryRepository $repository
     * @param FeedInterface[]     $feeds
     */
    public function __construct(FeedEntryRepository $repository, $feeds)
    {
        $this->repository = $repository;
        if ($feeds instanceof \Traversable) {
            $feeds = iterator_to_array($feeds);
        }
        $this->feeds = $feeds;
    }

    public function slice($offset, $length = null)
    {
        return $this->repository->findByFeeds($this->feeds, $offset, $length);
    }

    private function __load___()
    {
        $this->entries = new ArrayCollection($this->repository->findByFeeds($this->feeds));
    }

    public function count()
    {
        if (null === $this->entries) {
            return $this->repository->countByFeeds($this->feeds);
        }

        return $this->entries->count();
    }

    public function toArray()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->toArray();
    }

    public function first()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->first();
    }

    public function last()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->last();
    }

    public function key()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->key();
    }

    public function next()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->next();
    }

    public function current()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->current();
    }

    public function remove($key)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->remove($key);
    }

    public function removeElement($element)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->removeElement($element);
    }

    public function offsetExists($offset)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->offsetUnset($offset);
    }

    public function containsKey($key)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->containsKey($key);
    }

    public function contains($element)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->contains($element);
    }

    public function exists(Closure $p)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->exists($p);
    }

    public function indexOf($element)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->indexOf($element);
    }

    public function get($key)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->get($key);
    }

    public function getKeys()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->getKeys();
    }
    public function getValues()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->getValues();
    }

    public function set($key, $value)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        $this->entries->set($key, $value);
    }

    public function add($value)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->add($value);
    }

    public function isEmpty()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->isEmpty();
    }

    public function getIterator()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->getIterator();
    }

    public function map(Closure $func)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->map($func);
    }

    public function filter(Closure $p)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->filter($p);
    }

    public function forAll(Closure $p)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->forAll($p);
    }

    public function partition(Closure $p)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->partition($p);
    }

    public function clear()
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        $this->entries->clear();
    }

    public function matching(Criteria $criteria)
    {
        if (null === $this->entries) {
            $this->__load___();
        }

        return $this->entries->matching($criteria);
    }
}
