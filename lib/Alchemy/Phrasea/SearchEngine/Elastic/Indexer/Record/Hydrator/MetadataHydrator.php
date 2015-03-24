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

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
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
            (SELECT record_id, ms.name AS `key`, m.value AS value, 'caption' AS type, ms.business AS private
            FROM metadatas AS m
            INNER JOIN metadatas_structure AS ms ON (ms.id = m.meta_struct_id)
            WHERE record_id IN (?))

            UNION

            (SELECT record_id, t.name AS `key`, t.value AS value, 'exif' AS type, 0 AS private
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
            $key = $metadata['key'];
            $value = $metadata['value'];

            // Do not keep empty values
            if (empty($key) || empty($value)) {
                continue;
            }

            $id = $metadata['record_id'];
            if (isset($records[$id])) {
                $record =& $records[$id];
            } else {
                throw new Exception('Received metadata from unexpected record');
            }

            switch ($metadata['type']) {
                case 'caption':
                    // Private caption fields are kept apart
                    $type = $metadata['private'] ? 'private_caption' : 'caption';
                    // Caption are multi-valued
                    if (!isset($record[$type][$key])) {
                        $record[$type][$key] = array();
                    }
                    $record[$type][$key][] = $value;
                    // Collect value in the "all" field
                    $field = sprintf('%s_all', $type);
                    if (!isset($record[$field])) {
                        $record[$field] = array();
                    }
                    $record[$field][] = $value;
                    break;

                case 'exif':
                    // EXIF data is single-valued
                    $record['exif'][$key] = $value;
                    break;

                default:
                    throw new Exception('Unexpected metadata type');
                    break;
            }
        }
    }
}
