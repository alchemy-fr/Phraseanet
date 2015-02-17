<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;

class MetadataHydrator implements HydratorInterface
{
    private $connection;

    public function __construct(DriverConnection $connection)
    {
        $this->connection = $connection;
    }

    public function hydrateRecords(array &$records)
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

        $ids = array_keys($records);
        $statement = $this->connection->executeQuery(
            $sql,
            array($ids, $ids),
            array(Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY)
        );

        while ($metadata = $statement->fetch()) {
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
}
