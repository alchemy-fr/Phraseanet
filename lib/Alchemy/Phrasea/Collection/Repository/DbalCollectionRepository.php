<?php

namespace Alchemy\Phrasea\Collection\Repository;

use Alchemy\Phrasea\Collection\CollectionFactory;
use Alchemy\Phrasea\Collection\CollectionRepository;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;
use Doctrine\DBAL\Connection;

class DbalCollectionRepository implements CollectionRepository
{

    private static $query = 'SELECT coll_id, asciiname, label_en, label_fr, label_de, label_nl, prefs, logo, majLogo, pub_wm
                                FROM coll';

    /**
     * @var int
     */
    private $databoxId;

    /**
     * @var CollectionReferenceRepository
     */
    private $referenceRepository;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        $databoxId,
        Connection $connection,
        CollectionReferenceRepository $referenceRepository,
        CollectionFactory $collectionFactory
    ) {
        $this->databoxId = (int) $databoxId;
        $this->connection = $connection;
        $this->referenceRepository = $referenceRepository;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return \collection[]
     */
    public function findAll()
    {
        $references = $this->referenceRepository->findAllByDatabox($this->databoxId);
        $params = [];

        foreach ($references as $reference) {
            $params[':id_' . $reference->getCollectionId()] = $reference->getCollectionId();
        }

        $query = self::$query . sprintf(' WHERE coll_id IN (%s)', implode(', ', array_keys($params)));
        $rows = $this->connection->fetchAll($query, $params);

        return $this->collectionFactory->createMany($this->databoxId, $references, $rows);
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

        $query = self::$query . ' WHERE coll_id = :collectionId';
        $row = $this->connection->fetchAssoc($query, [ ':collectionId' => $reference->getCollectionId() ]);

        if ($row !== false) {
            return $this->collectionFactory->create($this->databoxId, $reference, $row);
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

        $query = self::$query . ' WHERE coll_id = :collectionId';
        $row = $this->connection->fetchAssoc($query, [ ':collectionId' => $reference->getCollectionId() ]);

        if ($row !== false) {
            return $this->collectionFactory->create($this->databoxId, $reference, $row);
        }

        return null;
    }
}
