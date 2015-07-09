<?php

namespace Alchemy\Phrasea\Collection;

class ArrayCacheCollectionRepository implements CollectionRepository
{
    /**
     * @var CollectionRepository
     */
    private $collectionRepository;

    private $collectionCache = array();

    private $baseIdMap = array();

    private $databoxFlags = array();

    public function __construct(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    private function putCollectionsInCache(array $collections)
    {
        foreach ($collections as $collection) {
            $this->putCollectionInCache($collection);
        }
    }

    private function putCollectionInCache(\collection $collection = null)
    {
        if ($collection === null) {
            return;
        }

        $baseId = $collection->getReference()->getBaseId();
        $databoxId = $collection->getReference()->getDataboxId();
        $collectionId = $collection->getReference()->getCollectionId();

        if (! isset($this->collectionCache[$databoxId])) {
            $this->collectionCache[$databoxId] = [];
        }

        $this->collectionCache[$databoxId][$collectionId] = $collection;
        $this->baseIdMap[$baseId] = [ $databoxId, $collectionId ];
    }

    private function getCollectionInCache($databoxId, $collectionId)
    {
        if (isset($this->collectionCache[$databoxId][$collectionId])) {
            return $this->collectionCache[$databoxId][$collectionId];
        }

        return null;
    }

    private function getCollectionInCacheByBaseId($baseId)
    {
        if (isset($this->baseIdMap[$baseId])) {
            list ($databoxId, $collectionId) = $this->baseIdMap[$baseId];

            return $this->getCollectionInCache($databoxId, $collectionId);
        }

        return null;
    }

    private function getCollectionsInCache($databoxId)
    {
        if (isset($this->collectionCache[$databoxId])) {
            return $this->collectionCache[$databoxId];
        }

        return [];
    }

    /**
     * @param int $databoxId
     * @return \collection[]
     */
    public function findAllByDatabox($databoxId)
    {
        if (! isset($this->databoxFlags[$databoxId]) || $this->databoxFlags[$databoxId] !== true) {
            $this->putCollectionsInCache($this->collectionRepository->findAllByDatabox($databoxId));
            $this->databoxFlags[$databoxId] = true;
        }

        return $this->getCollectionsInCache($databoxId);
    }

    /**
     * @param int $baseId
     * @return \collection|null
     */
    public function find($baseId)
    {
        if (! isset($this->baseIdMap[$baseId])) {
            $this->putCollectionInCache($this->collectionRepository->find($baseId));
        }

        return $this->getCollectionInCacheByBaseId($baseId);
    }

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return \collection|null
     */
    public function findByCollectionId($databoxId, $collectionId)
    {
        if (! isset($this->collectionCache[$databoxId][$collectionId])) {
            $this->putCollectionInCache($this->collectionRepository->findByCollectionId($databoxId, $collectionId));
        }

        return $this->getCollectionInCache($databoxId, $collectionId);
    }
}
