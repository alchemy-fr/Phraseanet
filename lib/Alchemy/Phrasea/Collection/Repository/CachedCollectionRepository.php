<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Collection\Repository;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\CollectionRepository;
use Doctrine\Common\Cache\Cache;

final class CachedCollectionRepository implements CollectionRepository
{

    /**
     * @var Application
     */
    private $app;

    /**
     * @var CollectionRepository
     */
    private $repository;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * @param Application $application
     * @param CollectionRepository $repository
     * @param Cache $cache
     * @param $cacheKey
     */
    public function __construct(Application $application, CollectionRepository $repository, Cache $cache, $cacheKey)
    {
        $this->app = $application;
        $this->repository = $repository;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * @return \collection[]
     */
    public function findAll()
    {
        $cacheKey = hash('sha256', $this->cacheKey . '.findAll');
        /** @var \collection[] $collections */
        $collections = $this->cache->fetch($cacheKey);

        if ($collections === false) {
            $collections = $this->repository->findAll();
            $this->putInCache($cacheKey, $collections);
        } else {
            foreach ($collections as $collection) {
                $collection->hydrate($this->app);
            }
        }

        return $collections;
    }

    /**
     * @param int $collectionId
     * @return \collection|null
     */
    public function find($collectionId)
    {
        $collections = $this->findAll();

        if (isset($collections[$collectionId])) {
            return $collections[$collectionId];
        }

        return null;
    }

    private function putInCache($key, $value)
    {
        $this->cache->save($key, $value);
    }
}
