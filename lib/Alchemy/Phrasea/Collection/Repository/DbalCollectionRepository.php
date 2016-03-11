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

    private static $deleteQuery = 'DELETE FROM coll WHERE coll_id = :collectionId';

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
    private $databoxConnection;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param $databoxId
     * @param Connection $databoxConnection
     * @param CollectionReferenceRepository $referenceRepository
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        $databoxId,
        Connection $databoxConnection,
        CollectionReferenceRepository $referenceRepository,
        CollectionFactory $collectionFactory
    ) {
        $this->databoxId = (int) $databoxId;
        $this->databoxConnection = $databoxConnection;
        $this->referenceRepository = $referenceRepository;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return \collection[]
     */
    public function findAll()
    {
        $references = $this->referenceRepository->findAllByDatabox($this->databoxId);

        if (empty($references)) {
            return [];
        }

        $parameters = [];

        foreach ($references as $reference) {
            $parameters[] = $reference->getCollectionId();
        }

        $query = self::$selectQuery . ' WHERE coll_id IN (:collectionIds)';
        $parameters = ['collectionIds' => $parameters];
        $parameterTypes = ['collectionIds' => Connection::PARAM_INT_ARRAY];

        $rows = $this->databoxConnection->fetchAll($query, $parameters, $parameterTypes);

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
        $row = $this->databoxConnection->fetchAssoc($query, [':collectionId' => $reference->getCollectionId()]);

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
        $row = $this->databoxConnection->fetchAssoc($query, [':collectionId' => $reference->getCollectionId()]);

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

        $this->databoxConnection->executeQuery($query, $parameters);

        if ($isInsert) {
            $collection->setCollectionId($this->databoxConnection->lastInsertId());
        }
    }

    public function delete(Collection $collection)
    {
        $parameters = [
            'collectionId' => $collection->getCollectionId()
        ];

        $this->databoxConnection->executeQuery(self::$deleteQuery, $parameters);
    }
}
