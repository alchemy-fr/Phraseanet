<?php

namespace Alchemy\Phrasea\Collection\Repository;

use Alchemy\Phrasea\Collection\Collection;
use Alchemy\Phrasea\Collection\CollectionFactory;
use Alchemy\Phrasea\Collection\CollectionRepository;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;
use Doctrine\DBAL\Connection;

class DbalCollectionRepository implements CollectionRepository
{

    private static $selectQuery = 'SELECT coll_id, asciiname, label_en, label_fr, label_de, label_nl, prefs, logo, majLogo, pub_wm
                                FROM coll';

    private static $insertQuery = 'INSERT INTO coll (asciiname, prefs, logo) VALUES (:name, :preferences, :logo)';

    private static $updateQuery = 'UPDATE coll SET asciiname = :name, label_en = :labelEn, label_fr = :labelFr,
                                label_de = :labelDe, label_nl = :labelNl, prefs = :preferences, logo = :logo,
                                majLogo = :logoTimestamp, pub_wm = :publicWatermark WHERE coll_id = :collectionId';

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

    /**
     * @param $databoxId
     * @param Connection $connection
     * @param CollectionReferenceRepository $referenceRepository
     * @param CollectionFactory $collectionFactory
     */
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

        $query = self::$selectQuery . sprintf(' WHERE coll_id IN (%s)', implode(', ', array_keys($params)));
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

        $query = self::$selectQuery . ' WHERE coll_id = :collectionId';
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

        $query = self::$selectQuery . ' WHERE coll_id = :collectionId';
        $row = $this->connection->fetchAssoc($query, [ ':collectionId' => $reference->getCollectionId() ]);

        if ($row !== false) {
            return $this->collectionFactory->create($this->databoxId, $reference, $row);
        }

        return null;
    }

    public function save(Collection $collection)
    {
        $isInsert = true;
        $query = self::$insertQuery;
        $parameters = array(
            'name' => $collection->getName(),
            'preferences' => $collection->getPreferences(),
            'logo' => $collection->getLogo()
        );

        if ($collection->getCollectionId() > 0) {
            $parameters['collectionId'] = $collection->getCollectionId();
            $parameters['labelEn'] = $collection->getLabel('en', false);
            $parameters['labelFr'] = $collection->getLabel('fr', false);
            $parameters['labelDe'] = $collection->getLabel('de', false);
            $parameters['labelNl'] = $collection->getLabel('nl', false);
            $parameters['logoTimestamp'] = $collection->getLogoUpdatedAt();
            $parameters['publicWatermark'] = $collection->getPublicWatermark();

            $query = self::$updateQuery;
            $isInsert = false;
        }

        $this->connection->executeQuery($query, $parameters);

        if ($isInsert) {
            $collection->setCollectionId($this->connection->lastInsertId());
        }
    }
}
