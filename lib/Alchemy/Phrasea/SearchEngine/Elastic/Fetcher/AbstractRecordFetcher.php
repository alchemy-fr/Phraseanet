<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Fetcher;

use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Doctrine\DBAL\Connection;
use databox;

abstract class AbstractRecordFetcher
{
    protected $statementRecords;
    protected $connection;

    protected $offset = 0;
    protected $batchSize = 1;

    private $helper;
    private $databox;

    private $postFetch;

    public function __construct(databox $databox, RecordHelper $helper)
    {
        $this->connection = $databox->get_connection();
        $this->databox  = $databox;
        $this->helper = $helper;
    }

    public function fetch()
    {
        $statementRecords = $this->statementRecords();

        if (php_sapi_name() === 'cli' && ($this->offset !== 0 || $statementRecords->rowCount() <= 0)) {
            printf("Query %d/%d -> %d rows on database %s\n", $this->offset, $this->batchSize, $statementRecords->rowCount(), $this->databox->get_dbname());
        }

        $records = [];

        while ($record = $statementRecords->fetch()) {
            $records[$record['record_id']] = $record;
            $this->offset++;
        }

        if (count($records) < 1) {
            if (php_sapi_name() === 'cli') {
                printf("End of records\n");
            }

            return false;
        }

        $this->addTitleToRecord($records);
        $this->addMetadataToRecords($records);
        $this->addSubDefinitionsToRecord($records);

        // Hydrate records
        foreach ($records as $key => $record) {
            $records[$key] = $this->hydrate($record);
        }

        if (is_callable($this->postFetch)) {
            call_user_func($this->postFetch, $records);
        }

        return $records;
    }

    public function setPostFetch(\Closure $callable)
    {
        $this->postFetch = $callable;
    }

    public function setBatchSize($size)
    {
        if ($size < 1) {
            throw new \LogicException("Batch size must be greater than or equal to 1");
        }
        $this->batchSize = (int) $size;
    }

    private function hydrate(array $record)
    {
        // Some casting
        $record['record_id'] = (int) $record['record_id'];
        $record['collection_id'] = (int) $record['collection_id'];
        // Some identifiers
        $record['id'] = $this->helper->getUniqueRecordId($this->databox->get_sbas_id(), $record['record_id']);
        $record['base_id'] = $this->helper->getUniqueCollectionId($this->databox->get_sbas_id(), $record['collection_id']);
        $record['databox_id'] = $this->databox->get_sbas_id();

        if ((int) $record['parent_record_id'] === 1) {
            $record['record_type'] = SearchEngineInterface::GEM_TYPE_STORY;
        } else {
            $record['record_type'] = SearchEngineInterface::GEM_TYPE_RECORD;
        }

        if (false == $record['mime']) {
            $record['mime'] = 'application/octet-stream';
        }

        unset($record['parent_record_id']);

        return $record;
    }

    private function execStatementMetadata($ids)
    {
        $sql = <<<SQL
            (SELECT record_id, ms.name AS metadata_key, m.value AS metadata_value, 'caption' AS metadata_type, ms.business AS metadata_private
            FROM metadatas AS m
            INNER JOIN metadatas_structure AS ms ON (ms.id = m.meta_struct_id)
            WHERE record_id IN (?))

            UNION

            (SELECT record_id, t.name AS metadata_key, t.value AS metadata_value, 'exif' AS metadata_type, 0 AS metadata_private
            FROM technical_datas AS t
            WHERE record_id IN (?))
SQL;

        return $this->connection->executeQuery($sql, array($ids, $ids), array(Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY));
    }

    private function addMetadataToRecords(&$records)
    {
        $statementMetadata = $this->execStatementMetadata(array_keys($records));

        while ($metadata = $statementMetadata->fetch()) {
            // Store metadata value
            $value = $metadata['metadata_value'];
            $key = $metadata['metadata_key'];
            $type = $metadata['metadata_type'];

            // Do not keep empty values
            if (empty($value)) {
                continue;
            }

            if ($metadata['metadata_private']) {
                $type = 'private_'.$type;
            }

            // Metadata can be multi-valued
            if (!isset($records[$metadata['record_id']] [$type][$key])) {
                $records[$metadata['record_id']][$type][$key] = $value;
            } elseif (is_array($records[$metadata['record_id']] [$type][$key])) {
                $records[$metadata['record_id']][$type][$key][] = $value;
            } else {
                $records[$metadata['record_id']][$type][$key] = array($records[$metadata['record_id']][$type][$key], $value);
            }
        }
    }

    private function execStatementTitle($ids)
    {
        $sql = <<<SQL
            SELECT
                m.`record_id`,
                CASE ms.`thumbtitle`
                  WHEN "1" THEN "default"
                  WHEN "0" THEN "default"
                  ELSE ms.`thumbtitle`
                END AS locale,
                CASE ms.`thumbtitle`
                  WHEN "0" THEN r.`originalname`
                  ELSE GROUP_CONCAT(m.`value` ORDER BY ms.`thumbtitle`, ms.`sorter` SEPARATOR " - ")
                END AS title
            FROM metadatas AS m FORCE INDEX(`record_id`)
            STRAIGHT_JOIN metadatas_structure AS ms ON (ms.`id` = m.`meta_struct_id`)
            STRAIGHT_JOIN record AS r ON (r.`record_id` = m.`record_id`)
            WHERE m.`record_id` IN (?)
            GROUP BY m.`record_id`, ms.`thumbtitle`
SQL;

        return $this->connection->executeQuery($sql, array($ids), array(Connection::PARAM_INT_ARRAY));
    }

    private function addTitleToRecord(&$records)
    {
        $statementTitle = $this->execStatementTitle(array_keys($records));

        while ($row = $statementTitle->fetch()) {
            $records[$row['record_id']]['title'][$row['locale']] = $row['title'];
        }
    }

    private function addSubDefinitionsToRecord(&$records)
    {
        $statementSubDef = $this->execStatementSubDefinitions(array_keys($records));

        while ($subDefinitions = $statementSubDef->fetch()) {
            $records[$subDefinitions['record_id']]['subdefs'][$subDefinitions['name']] = array(
                'path' => $subDefinitions['path'],
                'width' => $subDefinitions['width'],
                'height' => $subDefinitions['height'],
            );
        }
    }

    private function execStatementSubDefinitions($ids)
    {
        $sql = <<<SQL
            SELECT
              s.record_id,
              s.name,
              s.height,
              s.width,
              CONCAT(TRIM(TRAILING '/' FROM s.path), '/', s.file) AS path
            FROM subdef s
            WHERE s.record_id IN (?)
            AND s.name IN ('thumbnail', 'preview', 'thumbnailgif')
SQL;

        return $this->connection->executeQuery($sql, array($ids), array(Connection::PARAM_INT_ARRAY));

    }

    /** Provides PDO Statement that fetches records */
    abstract protected function statementRecords();
}
