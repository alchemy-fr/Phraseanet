<?php

namespace Alchemy\Phrasea\Collection\Reference;

use Doctrine\DBAL\Connection;

class DbalCollectionReferenceRepository implements CollectionReferenceRepository
{

    private static $query = 'SELECT base_id AS baseId, sbas_id AS databoxId, server_coll_id AS collectionId,
                                    ord AS displayIndex, active AS isActive, aliases AS alias
                              FROM bas';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return CollectionReference[]
     */
    public function findAll()
    {
        return $this->createManyReferences($this->connection->fetchAll(self::$query));
    }

    /**
     * @param int $databoxId
     * @return CollectionReference[]
     */
    public function findAllByDatabox($databoxId)
    {
        $query = self::$query . ' WHERE sbas_id = :databoxId';
        $rows = $this->connection->fetchAll($query, [ ':databoxId' => $databoxId ]);

        return $this->createManyReferences($rows);
    }

    /**
     * @param int $baseId
     * @return CollectionReference|null
     */
    public function find($baseId)
    {
        $query = self::$query . ' WHERE base_id = :baseId';
        $row = $this->connection->fetchAssoc($query, [ ':baseId' => $baseId ]);

        if ($row !== false) {
            return $this->createReference($row);
        }

        return null;
    }

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return CollectionReference|null
     */
    public function findByCollectionId($databoxId, $collectionId)
    {
        $query = self::$query . ' WHERE sbas_id = :databoxId AND server_coll_id = :collectionId';
        $row = $this->connection->fetchAssoc($query, [ ':databoxId' => $databoxId, ':collectionId' => $collectionId ]);

        if ($row !== false) {
            return $this->createReference($row);
        }

        return null;
    }

    /**
     * @param array $row
     * @return CollectionReference
     */
    private function createReference(array $row)
    {
        return new CollectionReference(
            $row['baseId'],
            $row['databoxId'],
            $row['collectionId'],
            $row['displayIndex'],
            $row['isActive'],
            $row['alias']
        );
    }

    /**
     * @param $rows
     * @return array
     */
    private function createManyReferences($rows)
    {
        $references = [];

        foreach ($rows as $row) {
            $references[$row['baseId']] = $this->createReference($row);
        }

        return $references;
    }
}
