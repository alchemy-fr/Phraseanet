<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox\Record;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Cache\Exception;
use Doctrine\DBAL\Connection;

class LegacyRecordRepository implements RecordRepository
{
    /** @var Application */
    private $app;
    /** @var \databox */
    private $databox;

    public function __construct(Application $app, \databox $databox)
    {
        $this->app = $app;
        $this->databox = $databox;
    }

    public function find($record_id, $number = null)
    {
        $record = new \record_adapter($this->app, $this->databox->get_sbas_id(), $record_id, $number, false);
        try {
            $data = $record->get_data_from_cache();
        } catch (Exception $exception) {
            $data = false;
        }

        if (false === $data) {
            static $sql;

            if (!$sql) {
                $sql = $this->createSelectBuilder()->where('record_id = :record_id')->getSQL();
            }

            $data = $this->databox->get_connection()->fetchAssoc($sql, ['record_id' => $record_id]);
        }

        if (false === $data) {
            return null;
        }

        return $this->mapRecordFromResultRow($data, $record);
    }

    /**
     * @param string $sha256
     * @return \record_adapter[]
     */
    public function findBySha256($sha256)
    {
        static $sql;

        if (!$sql) {
            $sql = $this->createSelectBuilder()->where('sha256 = :sha256')->getSQL();
        }

        $result = $this->databox->get_connection()->fetchAll($sql, ['sha256' => $sha256]);

        return $this->mapRecordsFromResultSet($result);
    }

    /**
     * @param string $uuid
     * @return \record_adapter[]
     */
    public function findByUuid($uuid)
    {
        static $sql;

        if (!$sql) {
            $sql = $this->createSelectBuilder()->where('uuid = :uuid')->getSQL();
        }

        $result = $this->databox->get_connection()->fetchAll($sql, ['uuid' => $uuid]);

        return $this->mapRecordsFromResultSet($result);
    }

    public function findByRecordIds(array $recordIds)
    {
        static $sql;

        if (empty($recordIds)) {
            return [];
        }

        if (!$sql) {
            $sql = $this->createSelectBuilder()->where('record_id IN (:recordIds)')->getSQL();
        }

        $result = $this->databox->get_connection()->fetchAll(
            $sql,
            ['recordIds' => $recordIds],
            ['recordIds' => Connection::PARAM_INT_ARRAY]
        );

        return $this->mapRecordsFromResultSet($result);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function createSelectBuilder()
    {
        $connection = $this->databox->get_connection();

        return $connection->createQueryBuilder()
            ->select(
                'coll_id AS collection_id',
                'record_id',
                'credate AS created',
                'uuid',
                'moddate AS updated',
                'parent_record_id AS isStory',
                $connection->quoteIdentifier('type'),
                'originalname AS originalName',
                'sha256',
                'mime'
            )
            ->from('record', 'r');
    }

    /**
     * @param array $result
     * @return \record_adapter[]
     */
    private function mapRecordsFromResultSet(array $result)
    {
        $records = [];

        foreach ($result as $row) {
            $records[] = $this->mapRecordFromResultRow($row);
        }

        return $records;
    }

    /**
     * @param array                $row
     * @param \record_adapter|null $record
     * @return \record_adapter
     */
    private function mapRecordFromResultRow(array $row, \record_adapter $record = null)
    {
        if (null === $record) {
            $record = new \record_adapter($this->app, $this->databox->get_sbas_id(), $row['record_id'], null, false);
        }

        $record->mapFromData($row);
        $record->putInCache();

        return $record;
    }
}
