<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Collection\Factory;

use App\Collection\CollectionRepository;
use App\Collection\CollectionRepositoryFactory;
use App\Collection\Repository\ArrayCacheCollectionRepository;

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
