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

use Alchemy\Phrasea\Application;
use Doctrine\Common\Cache\Cache;

class CachedDataboxRepository implements DataboxRepositoryInterface
{
    const CACHE_KEY = \appbox::CACHE_LIST_BASES;

    /** @var DataboxRepositoryInterface */
    private $repository;
    /** @var Cache */
    private $cache;
    /** @var DataboxFactory */
    private $factory;

    public function __construct(DataboxRepositoryInterface $repository, Cache $cache, DataboxFactory $factory)
    {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->factory = $factory;
    }

    public function find($id)
    {
        $rows = $this->cache->fetch(self::CACHE_KEY);

        if (isset($rows[$id])) {
            return $this->factory->create($id, $rows[$id]);
        }

        return $this->repository->find($id);
    }

    public function findAll()
    {
        $rows = $this->cache->fetch(self::CACHE_KEY);

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
            $rows[$databox->get_sbas_id()] = $databox->getAsRow();
        }

        $this->cache->save(self::CACHE_KEY, $rows);
    }
}
