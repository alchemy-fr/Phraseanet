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
use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\Utilities\StringHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use DomainException;
use InvalidArgumentException;

class MetadataHydrator implements HydratorInterface
{
    private $connection;
    private $structure;
    private $helper;

    private $gps_position_buffer = [];

    public function __construct(DriverConnection $connection, Structure $structure, RecordHelper $helper)
    {
        $this->connection = $connection;
        $this->structure = $structure;
        $this->helper = $helper;
    }

    public function hydrateRecords(array &$records)
    {
        $sql = "(SELECT record_id, ms.name AS `key`, m.value AS value, 'caption' AS type, ms.business AS private\n"
             . " FROM metadatas AS m INNER JOIN metadatas_structure AS ms ON (ms.id = m.meta_struct_id)\n"
             . " WHERE record_id IN (?))\n"
             . "UNION\n"
             . "(SELECT record_id, t.name AS `key`, t.value AS value, 'exif' AS type, 0 AS private\n"
             . " FROM technical_datas AS t\n"
             . " WHERE record_id IN (?))\n";

        $ids = array_keys($records);
        $statement = $this->connection->executeQuery(
            $sql,
            array($ids, $ids),
            array(Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY)
        );

        while ($metadata = $statement->fetch()) {
            // Store metadata value
            $key = $metadata['key'];
            $value = trim($metadata['value']);

            // Do not keep empty values
            if ($key === '' || $value === '') {
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
                    // Sanitize fields
                    $value = StringHelper::crlfNormalize($value);
                    $value = $this->helper->sanitizeValue($value, $this->structure->typeOf($key));
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
                    if (GpsPosition::isSupportedTagName($key)) {
                        $this->handleGpsPosition($records, $id, $key, $value);
                        break;
                    }
                    $tag = $this->structure->getMetadataTagByName($key);
                    if ($tag) {
                        $value = $this->helper->sanitizeValue($value, $tag->getType());
                    }
                    // EXIF data is single-valued
                    $record['metadata_tags'][$key] = $value;
                    break;

                default:
                    throw new Exception('Unexpected metadata type');
                    break;
            }
        }

        $this->clearGpsPositionBuffer();
    }

    private function handleGpsPosition(&$records, $id, $tag_name, $value)
    {
        // Get position object
        if (!isset($this->gps_position_buffer[$id])) {
            $this->gps_position_buffer[$id] = new GpsPosition();
        }
        $position = $this->gps_position_buffer[$id];
        // Push this tag into object
        $position->set($tag_name, $value);
        // Try to output complete position
        if ($position->isComplete()) {
            $lon = $position->getSignedLongitude();
            $lat = $position->getSignedLatitude();

            $records[$id]['metadata_tags']['Longitude'] = $lon;
            $records[$id]['metadata_tags']['Latitude'] = $lat;

            $records[$id]["location"] = [
                "lat" => $lat,
                "lon" => $lon
            ];

            unset($this->gps_position_buffer[$id]);
        }
    }

    private function clearGpsPositionBuffer()
    {
        $this->gps_position_buffer = [];
    }
}
