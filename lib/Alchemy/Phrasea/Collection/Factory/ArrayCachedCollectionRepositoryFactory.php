<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
