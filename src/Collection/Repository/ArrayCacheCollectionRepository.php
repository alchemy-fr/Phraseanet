<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Collection\Repository;

use App\Collection\Collection;
use App\Collection\CollectionRepository;

class ArrayCacheCollectionRepository implements CollectionRepository
{
    /**
     * @var CollectionRepository
     */
    private $collectionRepository;

    /**
     * @var \App\Utils\collection[]|null
     */
    private $collectionCache = null;

    public function __construct(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @return \App\Utils\collection[]
     */
    public function findAll()
    {
        if ($this->collectionCache === null) {
            $this->collectionCache = $this->collectionRepository->findAll();
        }

        return $this->collectionCache;
    }

    /**
     * @param int $collectionId
     * @return \App\Utils\collection|null
     */
    public function find($collectionId)
    {
        $collections = $this->findAll();

        if (isset($collections[$collectionId])) {
            return $collections[$collectionId];
        }

        return null;
    }

    public function save(Collection $collection)
    {
        $this->collectionRepository->save($collection);

        if ($this->collectionCache !== null) {
            $this->collectionCache = null;
        }
    }

    public function delete(Collection $collection)
    {
        $this->collectionRepository->delete($collection);

        if (isset($this->collectionCache[$collection->getCollectionId()])) {
            unset($this->collectionCache[$collection->getCollectionId()]);
        }
    }
}
