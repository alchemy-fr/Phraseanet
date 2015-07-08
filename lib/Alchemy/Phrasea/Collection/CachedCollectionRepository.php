<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Collection;

use Doctrine\Common\Cache\Cache;

final class CachedCollectionRepository implements CollectionRepository
{

    /** @var CollectionRepository */
    private $repository;
    /** @var Cache */
    private $cache;
    /** @var string */
    private $cacheKey;

    public function __construct(CollectionRepository $repository, Cache $cache, $cacheKey)
    {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * @param int $databoxId
     * @return \collection[]
     */
    public function findAllByDatabox($databoxId)
    {
        $cacheKey = hash('sha256', $this->cacheKey . '.findAll.' . $databoxId);
        $collections = $this->cache->fetch($cacheKey);

        if ($collections === false) {
            $collections = $this->repository->findAllByDatabox($databoxId);
            $this->save($cacheKey, $collections);
        }

        return $collections;
    }

    /**
     * @param int $baseId
     * @return \collection|null
     */
    public function find($baseId)
    {
        $cacheKey = hash('sha256', $this->cacheKey . '.find.' . $baseId);
        $collection = $this->cache->fetch($cacheKey);

        if ($collection === false) {
            $collection = $this->repository->find($baseId);
            $this->save($cacheKey, $collection);
        }

        return $collection;
    }

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return \collection|null
     */
    public function findByCollectionId($databoxId, $collectionId)
    {
        $cacheKey = hash('sha256', $this->cacheKey . '.findByCollection.' . $databoxId . $collectionId);
        $collection = $this->cache->fetch($cacheKey);

        if ($collection === false) {
            $collection = $this->repository->findByCollectionId($databoxId, $collectionId);
            $this->save($cacheKey, $collection);
        }

        return $collection;
    }

    private function save($key, $value)
    {
        $this->cache->save($key, $value);
    }
}
