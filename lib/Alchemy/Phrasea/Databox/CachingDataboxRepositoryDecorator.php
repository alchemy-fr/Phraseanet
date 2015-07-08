<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

use Doctrine\Common\Cache\Cache;

final class CachingDataboxRepositoryDecorator implements DataboxRepository
{
    /** @var DataboxRepository */
    private $repository;
    /** @var Cache */
    private $cache;
    /** @var string */
    private $cacheKey;
    /** @var DataboxFactory */
    private $factory;

    public function __construct(DataboxRepository $repository, Cache $cache, $cacheKey, DataboxFactory $factory)
    {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
        $this->factory = $factory;
    }

    public function find($id)
    {
        $rows = $this->cache->fetch($this->cacheKey);

        if (isset($rows[$id])) {
            return $this->factory->create($id, $rows[$id]);
        }

        return $this->repository->find($id);
    }

    public function findAll()
    {
        $rows = $this->cache->fetch($this->cacheKey);

        if (is_array($rows)) {
            return $this->factory->createMany($rows);
        }

        $databoxes = $this->repository->findAll();

        $this->saveCache($databoxes);

        return $databoxes;
    }

    /**
     * @param \databox[] $databoxes
     */
    private function saveCache(array $databoxes)
    {
        $rows = array();

        foreach ($databoxes as $databox) {
            $rows[$databox->get_sbas_id()] = $databox->getRawData();
        }

        $this->cache->save($this->cacheKey, $rows);
    }
}
