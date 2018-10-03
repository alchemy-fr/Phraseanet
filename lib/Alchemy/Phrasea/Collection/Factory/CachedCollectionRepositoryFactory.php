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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\CollectionRepository;
use Alchemy\Phrasea\Collection\CollectionRepositoryFactory;
use Alchemy\Phrasea\Collection\Repository\CachedCollectionRepository;
use Doctrine\Common\Cache\Cache;

class CachedCollectionRepositoryFactory implements CollectionRepositoryFactory
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var CollectionRepositoryFactory
     */
    private $collectionRepositoryFactory;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $baseCacheKey;

    /**
     * @param Application $application
     * @param CollectionRepositoryFactory $collectionRepositoryFactory
     * @param Cache $cache
     * @param string $baseCacheKey
     */
    public function __construct(
        Application $application,
        CollectionRepositoryFactory $collectionRepositoryFactory,
        Cache $cache,
        $baseCacheKey
    ) {
        $this->application = $application;
        $this->collectionRepositoryFactory = $collectionRepositoryFactory;
        $this->cache = $cache;
        $this->baseCacheKey = (string)$baseCacheKey;
    }

    /**
     * @param int $databoxId
     * @return CollectionRepository
     */
    public function createRepositoryForDatabox($databoxId)
    {
        $repository = $this->collectionRepositoryFactory->createRepositoryForDatabox($databoxId);

        return new CachedCollectionRepository(
            $this->application,
            $repository,
            $this->cache,
            $this->baseCacheKey . '.' . $databoxId
        );
    }
}
