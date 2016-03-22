<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Collection\Reference;

class ArrayCacheCollectionReferenceRepository implements CollectionReferenceRepository
{
    /**
     * @var CollectionReferenceRepository
     */
    private $repository;

    /**
     * @var null|array
     */
    private $referenceCache = null;

    public function __construct(CollectionReferenceRepository $referenceRepository)
    {
        $this->repository = $referenceRepository;
    }

    /**
     * @return CollectionReference[]
     */
    public function findAll()
    {
        if ($this->referenceCache === null) {
            $this->referenceCache = $this->repository->findAll();
        }

        return $this->referenceCache;
    }

    /**
     * @param int $databoxId
     * @return CollectionReference[]
     */
    public function findAllByDatabox($databoxId)
    {
        $references = $this->findAll();
        $found = array();

        foreach ($references as $reference) {
            if ($reference->getDataboxId() == $databoxId) {
                $found[$reference->getBaseId()] = $reference;
            }
        }

        return $found;
    }

    /**
     * @param int $baseId
     * @return CollectionReference|null
     */
    public function find($baseId)
    {
        $references = $this->findAll();

        if (isset($references[$baseId])) {
            return $references[$baseId];
        }

        return null;
    }

    /**
     * @param array $baseIds
     * @return CollectionReference[]
     */
    public function findMany(array $baseIds)
    {
        $references = $this->findAll();

        $requested = [];

        foreach ($baseIds as $baseId) {
            if (isset($references[$baseId])) {
                $requested[] = $references[$baseId];
            }
        }

        return $requested;
    }

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return CollectionReference|null
     */
    public function findByCollectionId($databoxId, $collectionId)
    {
        $references = $this->findAll();

        foreach ($references as $reference) {
            if ($reference->getDataboxId() == $databoxId && $reference->getCollectionId() == $collectionId) {
                return $reference;
            }
        }

        return null;
    }

    /**
     * @param array|null $baseIdsSubset
     * @return CollectionReference[]
     */
    public function findHavingOrderMaster(array $baseIdsSubset = null)
    {
        return $this->repository->findHavingOrderMaster($baseIdsSubset);
    }

    /**
     * @param CollectionReference $reference
     * @return void
     */
    public function save(CollectionReference $reference)
    {
        $this->repository->save($reference);

        if ($this->referenceCache !== null) {
            $this->referenceCache[$reference->getBaseId()] = $reference;
        }
    }

    /**
     * @param CollectionReference $reference
     * @return void
     */
    public function delete(CollectionReference $reference)
    {
        $this->repository->delete($reference);

        if ($this->referenceCache !== null) {
            unset($this->referenceCache[$reference->getBaseId()]);
        }
    }
}
