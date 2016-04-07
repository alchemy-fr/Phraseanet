<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Subdef;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;

class DbalMediaSubdefDataRepository implements MediaSubdefDataRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param int[] $recordIds
     * @param string[]|null $names
     * @return array[]
     */
    public function findByRecordIdsAndNames(array $recordIds, array $names = null)
    {
        if (!$recordIds || (null !== $names && !$names)) {
            return [];
        }

        $sql = $this->getSelectSQL() . ' WHERE record_id IN (:recordIds)';
        $params = ['recordIds' => $recordIds];
        $types = ['recordIds' => Connection::PARAM_INT_ARRAY];

        if ($names) {
            $sql .= ' AND name IN (:names)';
            $params['names'] = $names;
            $types['names'] = Connection::PARAM_STR_ARRAY;
        }

        return $this->connection->fetchAll($sql, $params, $types);
    }

    /**
     * @param array[] $subdefIds
     * @return int The number of affected rows
     * @throws \Exception
     */
    public function delete(array $subdefIds)
    {
        if (!$subdefIds) {
            return 0;
        }

        $statement = $this->connection->prepare('DELETE FROM subdef WHERE record_id = :recordId AND name = :name');

        $this->connection->beginTransaction();

        try {
            $deleted = array_reduce($subdefIds, function ($carry, $data) use ($statement) {
                $carry += $statement->execute([
                    'recordId' => $data['record_id'],
                    'name' => $data['name'],
                ]);

                return $carry;
            }, 0);

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();

            throw $exception;
        }

        return $deleted;
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    public function save(array $data)
    {
        $this->connection->transactional(function () use ($data) {
            $partitions = $this->partitionInsertAndUpdate($data);

            $updateNeeded = $this->createMissing($partitions['insert']);

            $this->updatePresent($partitions['update'] + $updateNeeded);
        });
    }

    /**
     * @param array $data
     * @return array
     */
    private function partitionInsertAndUpdate(array $data)
    {
        $partitions = [
            'insert' => [],
            'update' => [],
        ];

        foreach ($data as $index => $item) {
            $partitions[isset($item['subdef_id']) ? 'update' : 'insert'][$index] = $item;
        }

        return $partitions;
    }

    /**
     * @param array $toInsert
     * @return array
     * @throws DBALException
     */
    private function createMissing(array $toInsert)
    {
        if (!$toInsert) {
            return [];
        }

        $updateNeeded = [];

        $sql = <<<'SQL'
INSERT INTO subdef (record_id, name, path, file, width, height, mime, size, substit, etag, created_on, updated_on, dispatched)
VALUES (:recordId, :name, :path, :file, :width, :height, :mime, :size, :substit, :etag, NOW(), NOW(), 1)
SQL;
        $statement = $this->connection->prepare($sql);

        foreach ($toInsert as $index => $partition) {
            try {
                $statement->execute($this->getParametersFromArray($partition));
            } catch (DriverException $exception) {
                $updateNeeded[$index] = $partition;
            }
        }

        return $updateNeeded;
    }

    /**
     * @param array $data
     * @return array
     */
    private function getParametersFromArray(array $data)
    {
        return [
            'recordId' => $data['record_id'],
            'name' => $data['name'],
            'path' => $data['path'],
            'file' => $data['file'],
            'width' => $data['width'],
            'height' => $data['height'],
            'mime' => $data['mime'],
            'size' => $data['size'],
            'substit' => $data['substit'],
            'etag' => $data['etag'],
        ];
    }

    /**
     * @param array $toUpdate
     * @throws DBALException
     */
    private function updatePresent(array $toUpdate)
    {
        if (!$toUpdate) {
            return;
        }

        $sql = <<<'SQL'
UPDATE subdef
SET path = :path, file = :file, width = :width, height = :height, mime = :mime,
 size = :size, substit = :substit, etag = :etag, updated_on = NOW()
WHERE record_id = :recordId AND name = :name
SQL;
        $statement = $this->connection->prepare($sql);

        foreach ($toUpdate as $index => $partition) {
            $statement->execute($this->getParametersFromArray($partition));
        }
    }

    private function getSelectSQL()
    {
        return <<<'SQL'
SELECT subdef_id, record_id, name, path, file, width, height, mime, size, substit, etag, created_on, updated_on
FROM subdef
SQL;
    }
}
