<?php

namespace Alchemy\Phrasea\Collection\Factory;

use Alchemy\Phrasea\Collection\CollectionRepository;
use Alchemy\Phrasea\Collection\CollectionRepositoryFactory;
use Alchemy\Phrasea\Collection\Repository\ArrayCacheCollectionRepository;

class ArrayCachedCollectionRepositoryFactory implements CollectionRepositoryFactory
{
    /**
     * @var CollectionRepositoryFactory
     */
    private $collectionRepositoryFactory;

    /**
     * @param CollectionRepositoryFactory $collectionRepositoryFactory
     */
    public function __construct(CollectionRepositoryFactory $collectionRepositoryFactory)
    {
        $this->collectionRepositoryFactory = $collectionRepositoryFactory;
    }

    /**
     * @param int $databoxId
     * @return CollectionRepository
     */
    public function createRepositoryForDatabox($databoxId)
    {
        $repository = $this->collectionRepositoryFactory->createRepositoryForDatabox($databoxId);

        return new ArrayCacheCollectionRepository($repository);
    }
}
