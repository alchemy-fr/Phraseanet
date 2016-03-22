<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Collection\Reference;

use Doctrine\DBAL\Connection;

class DbalCollectionReferenceRepository implements CollectionReferenceRepository
{

    private static $table = 'bas';

    private static $columns = [
        'base_id' => 'baseId',
        'sbas_id' => 'databoxId',
        'server_coll_id' => 'collectionId',
        'ord' => 'displayIndex',
        'active' => 'isActive',
        'aliases' => 'alias'
    ];

    private static $selectQuery = 'SELECT
 base_id AS baseId,
 sbas_id AS databoxId,
 server_coll_id AS collectionId,
 ord AS displayIndex,
 active AS isActive,
 aliases AS alias
FROM bas';

    private static $insertQuery = 'INSERT INTO bas (sbas_id, server_coll_id, ord, active, aliases)
VALUES (:databoxId, :collectionId, (SELECT COALESCE(MAX(b.ord), 0) + 1 AS ord FROM bas b WHERE b.sbas_id = :databoxId), :isActive, :alias)';

    private static $updateQuery = 'UPDATE bas SET
 ord = :displayIndex,
 active = :isActive,
 aliases = :alias
WHERE base_id = :baseId';

    private static $deleteQuery = 'DELETE FROM bas WHERE base_id = :baseId';

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
        return $this->createManyReferences($this->connection->fetchAll(self::$selectQuery));
    }

    /**
     * @param int $databoxId
     * @return CollectionReference[]
     */
    public function findAllByDatabox($databoxId)
    {
        $query = self::$selectQuery . ' WHERE sbas_id = :databoxId';
        $rows = $this->connection->fetchAll($query, [ ':databoxId' => $databoxId ]);

        return $this->createManyReferences($rows);
    }

    /**
     * @param int $baseId
     * @return CollectionReference|null
     */
    public function find($baseId)
    {
        $query = self::$selectQuery . ' WHERE base_id = :baseId';
        $row = $this->connection->fetchAssoc($query, [ ':baseId' => $baseId ]);

        if ($row !== false) {
            return $this->createReference($row);
        }

        return null;
    }

    /**
     * @param array $basesId
     * @return CollectionReference[]
     */
    public function findMany(array $basesId)
    {
        if (empty($basesId)) {
            return [];
        }

        $rows = $this->connection->fetchAll(
            self::$selectQuery . ' WHERE base_id IN (:baseIds)',
            ['baseIds' => $basesId],
            ['baseIds' => Connection::PARAM_INT_ARRAY]
        );

        return $this->createManyReferences($rows);
    }

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return CollectionReference|null
     */
    public function findByCollectionId($databoxId, $collectionId)
    {
        $query = self::$selectQuery . ' WHERE sbas_id = :databoxId AND server_coll_id = :collectionId';
        $row = $this->connection->fetchAssoc($query, [ ':databoxId' => $databoxId, ':collectionId' => $collectionId ]);

        if ($row !== false) {
            return $this->createReference($row);
        }

        return null;
    }

    public function findHavingOrderMaster(array $baseIdsSubset = null)
    {
        $query = self::$selectQuery
            . ' WHERE EXISTS(SELECT 1 FROM basusr WHERE basusr.order_master = 1 AND basusr.base_id = bas.base_id)';

        $parameters = [];
        $types = [];

        if (null !== $baseIdsSubset) {
            if (empty($baseIdsSubset)) {
                return [];
            }

            $query .= ' AND bas.base_id IN (:subset)';
            $parameters['subset'] = $baseIdsSubset;
            $types['subset'] = Connection::PARAM_INT_ARRAY;
        }

        $rows = $this->connection->fetchAll($query, $parameters, $types);

        return $this->createManyReferences($rows);
    }

    public function save(CollectionReference $collectionReference)
    {
        $query = self::$insertQuery;
        $isInsert = true;

        $parameters = [
            'isActive' => $collectionReference->isActive(),
            'alias' => $collectionReference->getAlias()
        ];

        if ($collectionReference->getBaseId() > 0) {
            $query = self::$updateQuery;
            $isInsert = false;

            $parameters['baseId'] = $collectionReference->getBaseId();
            $parameters['displayIndex'] = $collectionReference->getDisplayIndex();
        }
        else {
            $parameters['databoxId'] = $collectionReference->getDataboxId();
            $parameters['collectionId'] = $collectionReference->getCollectionId();
        }

        $this->connection->executeQuery($query, $parameters);

        if ($isInsert) {
            $collectionReference->setBaseId($this->connection->lastInsertId());
        }
    }

    /**
     * @param CollectionReference $collectionReference
     * @throws \Doctrine\DBAL\DBALException
     */
    public function delete(CollectionReference $collectionReference)
    {
        $parameters  = [
            'baseId' => $collectionReference->getBaseId()
        ];

        $this->connection->executeQuery(self::$deleteQuery, $parameters);
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
