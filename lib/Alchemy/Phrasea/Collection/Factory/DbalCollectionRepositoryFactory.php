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

use Alchemy\Phrasea\Collection\CollectionFactory;
use Alchemy\Phrasea\Collection\CollectionRepository;
use Alchemy\Phrasea\Collection\CollectionRepositoryFactory;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;
use Alchemy\Phrasea\Collection\Repository\DbalCollectionRepository;
use Alchemy\Phrasea\Databox\DataboxConnectionProvider;

class DbalCollectionRepositoryFactory implements CollectionRepositoryFactory
{

    /**
     * @var CollectionReferenceRepository
     */
    private $collectionReferenceRepository;

    /**
     * @var DataboxConnectionProvider
     */
    private $databoxConnectionProvider;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param DataboxConnectionProvider $connectionProvider
     * @param CollectionFactory $collectionFactory
     * @param CollectionReferenceRepository $referenceRepository
     */
    public function __construct(
        DataboxConnectionProvider $connectionProvider,
        CollectionFactory $collectionFactory,
        CollectionReferenceRepository $referenceRepository
    ) {
        $this->databoxConnectionProvider = $connectionProvider;
        $this->collectionFactory = $collectionFactory;
        $this->collectionReferenceRepository = $referenceRepository;
    }

    /**
     * @param int $databoxId
     * @return CollectionRepository
     */
    public function createRepositoryForDatabox($databoxId)
    {
        $databoxConnection = $this->databoxConnectionProvider->getConnection($databoxId);

        return new DbalCollectionRepository(
            $databoxId,
            $databoxConnection,
            $this->collectionReferenceRepository,
            $this->collectionFactory
        );
    }
}
