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

use appbox;
use DateTime;
use Exception;


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
     * @param string $value
     * @return null|string
     */
    public static function sanitizeDate($value)
    {
        $v_fix = null;
        try {
            $a = explode(';', preg_replace('/\D+/', ';', trim($value)));
            switch (count($a)) {
                case 1:     // yyyy
                    $date = new DateTime($a[0] . '-01-01');    // will throw if date is not valid
                    $v_fix = $date->format('Y');
                    break;
                case 2:     // yyyy;mm
                    $date = new DateTime( $a[0] . '-' . $a[1] . '-01');
                    $v_fix = $date->format('Y-m');
                    break;
                case 3:     // yyyy;mm;dd
                    $date = new DateTime($a[0] . '-' . $a[1] . '-' . $a[2]);
                    $v_fix = $date->format('Y-m-d');
                    break;
                case 4:
                    $date = new DateTime($a[0] . '-' . $a[1] . '-' . $a[2] . ' ' . $a[3] . ':00:00');
                    $v_fix = $date->format('Y-m-d H:i:s');
                    break;
                case 5:
                    $date = new DateTime($a[0] . '-' . $a[1] . '-' . $a[2] . ' ' . $a[3] . ':' . $a[4] . ':00');
                    $v_fix = $date->format('Y-m-d H:i:s');
                    break;
                case 6:
                    $date = new DateTime($a[0] . '-' . $a[1] . '-' . $a[2] . ' ' . $a[3] . ':' . $a[4] . ':' . $a[5]);
                    $v_fix = $date->format('Y-m-d H:i:s');
                    break;
            }
        } catch (Exception $e) {
            // no-op, v_fix = null
        }

        return $v_fix;
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
                $value = str_replace("\0", '', $value); // no null char for lucene !
                if( strlen($value) > 32766) {      // for lucene limit, before a better solution
                    for($l=32766; $l > 0; $l--) {
                        if(ord(substr($value, $l-1, 1)) < 128) {
                            break;
                        }
                    }
                    $value = substr($value, 0, $l);
                }
                return $value;

            default:
                return $value;
        }
    }
}
