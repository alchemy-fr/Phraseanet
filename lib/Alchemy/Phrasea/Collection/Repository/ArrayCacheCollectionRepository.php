<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Collection\Repository;

use Alchemy\Phrasea\Collection\Collection;
use Alchemy\Phrasea\Collection\CollectionRepository;

class ArrayCacheCollectionRepository implements CollectionRepository
{
    /**
     * @var CollectionRepository
     */
    private $collectionRepository;

    /**
     * @var \collection[]|null
     */
    private $collectionCache = null;

    public function __construct(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @return \collection[]
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

    public function clearCache()
    {
        $this->collectionCache = null;
        $this->collectionRepository->clearCache();
    }
}
