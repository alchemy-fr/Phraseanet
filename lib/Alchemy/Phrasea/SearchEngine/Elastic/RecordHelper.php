<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Flag;
use appbox;
use DateTime;
use igorw;

class RecordHelper
{
    /**
     * @var appbox
     */
    private $appbox;

    /**
     * @var int[][] Collection base IDs mapping by databox ID and collection ID
     */
    private $collectionMap;

    public function __construct(appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    public function getUniqueRecordId($databoxId, $recordId)
    {
        return sprintf('%d_%d', $databoxId, $recordId);
    }

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return int|null
     */
    public function getUniqueCollectionId($databoxId, $collectionId)
    {
        $col = $this->collectionMap();

        if (isset($col[$databoxId])) {
            if (isset($col[$databoxId][$collectionId])) {
                return $col[$databoxId][$collectionId];
            }
        }

        return null;
    }

    /**
     * @return int[][]
     * @throws \Doctrine\DBAL\DBALException
     */
    private function collectionMap()
    {
        if (!$this->collectionMap) {
            $map = array();
            $sql = 'SELECT
                        sbas_id as databox_id,
                        server_coll_id as collection_id,
                        base_id
                    FROM bas';

            $statement = $this->appbox->get_connection()->query($sql);

            while ($mapping = $statement->fetch()) {
                if (! isset($map[$mapping['databox_id']])) {
                    $map[$mapping['databox_id']] = [];
                }

                $map[$mapping['databox_id']][$mapping['collection_id']] = $mapping['base_id'];
            }

            $this->collectionMap = $map;
        }

        return $this->collectionMap;
    }

    /**
     * @param string $date
     * @return bool
     */
    public static function validateDate($date)
    {
        $d = DateTime::createFromFormat(FieldMapping::DATE_FORMAT_CAPTION_PHP, $date);

        return $d && $d->format(FieldMapping::DATE_FORMAT_CAPTION_PHP) == $date;
    }

    /**
     * @param string $value
     * @return null|string
     */
    public static function sanitizeDate($value)
    {
        // introduced in https://github.com/alchemy-fr/Phraseanet/commit/775ce804e0257d3a06e4e068bd17330a79eb8370#diff-bee690ed259e0cf73a31dee5295d2edcR286
        // not sure if it's really needed
        try {
            $date = new \DateTime($value);

            return $date->format(FieldMapping::DATE_FORMAT_CAPTION_PHP);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function sanitizeValue($value, $type)
    {
        switch ($type) {
            case FieldMapping::TYPE_DATE:
                return self::sanitizeDate($value);

            case FieldMapping::TYPE_FLOAT:
            case FieldMapping::TYPE_DOUBLE:
                return (float) $value;

            case FieldMapping::TYPE_INTEGER:
            case FieldMapping::TYPE_LONG:
            case FieldMapping::TYPE_SHORT:
            case FieldMapping::TYPE_BYTE:
                return (int) $value;

            case FieldMapping::TYPE_BOOLEAN:
                return (bool) $value;

            case FieldMapping::TYPE_STRING:
                return str_replace("\0", '', $value);

            default:
                return $value;
        }
    }

}
