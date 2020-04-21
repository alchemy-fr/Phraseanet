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

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
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
    private $conf;
    private $connection;
    private $structure;
    private $helper;

    private $position_fields_mapping;     // get from conf

    private $caption_gps_position;
    private $exif_gps_position;

    public function __construct(PropertyAccess $conf, DriverConnection $connection, Structure $structure, RecordHelper $helper)
    {
        $this->conf = $conf;
        $this->connection = $connection;
        $this->structure = $structure;
        $this->helper = $helper;

        // get the fieldnames of source of lat / lon geo fields (defined in instance conf)
        $this->position_fields_mapping = [];
        foreach($conf->get(['geocoding-providers'], []) as $provider) {
            if($provider['enabled'] && array_key_exists('position-fields', $provider)) {
                foreach ($provider['position-fields'] as $position_field) {
                    $this->position_fields_mapping[$position_field['name']] = $position_field['type'];
                }
            }
        }

        $this->caption_gps_position = new GpsPosition();
        $this->exif_gps_position = new GpsPosition();
    }

    public function hydrateRecords(array &$records)
    {
        $sql = "SELECT * FROM ("
            . "(SELECT record_id, ms.name AS `key`, m.value AS value, 'caption' AS type, ms.business AS private\n"
            . " FROM metadatas AS m INNER JOIN metadatas_structure AS ms ON (ms.id = m.meta_struct_id)\n"
            . " WHERE record_id IN (?))\n"
            . "UNION\n"
            . "(SELECT record_id, t.name AS `key`, t.value AS value, 'exif' AS type, 0 AS private\n"
            . " FROM technical_datas AS t\n"
            . " WHERE record_id IN (?))\n"
            . ") AS t ORDER BY record_id";

        $ids = array_keys($records);
        $statement = $this->connection->executeQuery(
            $sql,
            array($ids, $ids),
            array(Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY)
        );

        $record_id = -1;
        while ($metadata = $statement->fetch()) {

            if($metadata['record_id'] !== $record_id) {
                // record has changed, don't mix with previous one
                $this->caption_gps_position->clear();
                $this->exif_gps_position->clear();

                $record_id = $metadata['record_id'];
            }

            // Store metadata value
            $key = $metadata['key'];
            $value = trim($metadata['value']);

            // Do not keep empty values
            if ($key === '' || $value === '') {
                continue;
            }

            if (isset($records[$record_id])) {
                $record =& $records[$record_id];
            }
            else {
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

                    if(array_key_exists($key, $this->position_fields_mapping)) {
                        // this field is mapped as a position part (lat, lon, latlon), push it
                        switch($this->position_fields_mapping[$key]) {
                            case 'lat':
                                $this->handleGpsPosition($this->caption_gps_position, $record, GpsPosition::LATITUDE_TAG_NAME, $value);
                                break;
                            case 'lng':
                            case 'lon':
                                $this->handleGpsPosition($this->caption_gps_position, $record, GpsPosition::LONGITUDE_TAG_NAME, $value);
                                break;
                            case 'latlng':
                            case 'latlon':
                                $this->handleGpsPosition($this->caption_gps_position, $record, GpsPosition::FULL_GEO_NOTATION, $value);
                                break;
                        }
                    }

                    break;

                case 'exif':
                    // exif gps is a first-chance if caption is not yet set
                    // anyway if caption is set later, it will override the exif values
                    if (GpsPosition::isSupportedTagName($key) && !$this->caption_gps_position->isCompleteComposite()) {
                        $this->handleGpsPosition($this->exif_gps_position, $record, $key, $value);
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
    }

    private function handleGpsPosition(GpsPosition &$position, &$record, $tag_name, $value)
    {
        // Push this tag into object
        $position->set($tag_name, $value);

        // Try to output complete position
        if ($position->isCompleteComposite()) {
            $lon = $position->getCompositeLongitude();
            $lat = $position->getCompositeLatitude();

            $record['metadata_tags']['Longitude'] = $lon;
            $record['metadata_tags']['Latitude'] = $lat;

            $record["location"] = [
                "lat" => $lat,
                "lon" => $lon
            ];
        }
    }
}
