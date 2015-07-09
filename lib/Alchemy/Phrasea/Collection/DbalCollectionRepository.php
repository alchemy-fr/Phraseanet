<?php

namespace Alchemy\Phrasea\Collection;

use Alchemy\Phrasea\Databox\DataboxConnectionProvider;

class DbalCollectionRepository implements CollectionRepository
{

    private static $query = 'SELECT coll_id, asciiname, label_en, label_fr, label_de, label_nl, prefs, logo, majLogo, pub_wm
                                FROM coll';

    /**
     * @var CollectionReferenceRepository
     */
    private $referenceRepository;

    /**
     * @var DataboxConnectionProvider
     */
    private $connectionProvider;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        DataboxConnectionProvider $connectionProvider,
        CollectionReferenceRepository $referenceRepository,
        CollectionFactory $collectionFactory
    ) {
        $this->connectionProvider = $connectionProvider;
        $this->referenceRepository = $referenceRepository;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param int $databoxId
     * @return \collection[]
     */
    public function findAllByDatabox($databoxId)
    {
        $references = $this->referenceRepository->findAllByDatabox($databoxId);
        $connection = $this->connectionProvider->getConnection($databoxId);

        $params = [];

        foreach ($references as $reference) {
            $params[':id_' . $reference->getCollectionId()] = $reference->getCollectionId();
        }

        $query = self::$query . sprintf(' WHERE coll_id IN (%s)', implode(', ', array_keys($params)));
        $rows = $connection->fetchAll($query, $params);

        return $this->collectionFactory->createMany($databoxId, $references, $rows);
    }

    /**
     * @param int $baseId
     * @return \collection|null
     */
    public function find($baseId)
    {
        $reference = $this->referenceRepository->find($baseId);

        if ($reference === null) {
            return null;
        }

        $connection = $this->connectionProvider->getConnection($reference->getDataboxId());

        $query = self::$query . ' WHERE coll_id = :collectionId';
        $row = $connection->fetchAssoc($query, [ ':collectionId' => $reference->getCollectionId() ]);

        if ($row !== false) {
            return $this->collectionFactory->create($reference->getDataboxId(), $reference, $row);
        }

        return null;
    }

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return \collection|null
     */
    public function findByCollectionId($databoxId, $collectionId)
    {
        $reference = $this->referenceRepository->findByCollectionId($databoxId, $collectionId);

        if ($reference === null) {
            return null;
        }

        $connection = $this->connectionProvider->getConnection($databoxId);

        $query = self::$query . ' WHERE coll_id = :collectionId';
        $row = $connection->fetchAssoc($query, [ ':collectionId' => $reference->getCollectionId() ]);

        if ($row !== false) {
            return $this->collectionFactory->create($databoxId, $reference, $row);
        }

        return null;
    }
}
