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
    private $appbox;

    // Computation caches
    private $collectionMap;

    public function __construct(appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    public function getUniqueRecordId($databoxId, $recordId)
    {
        return sprintf('%d_%d', $databoxId, $recordId);
    }

    public function getUniqueCollectionId($databoxId, $collectionId)
    {
        $col = $this->collectionMap();

        if (isset($col[$databoxId])) {
            if (isset($col[$databoxId][$collectionId])) {
                return (int) $col[$databoxId][$collectionId];
            }
        }

        return null;
    }

    private function collectionMap()
    {
        if (!$this->collectionMap) {
            $connection = $this->appbox->get_connection();
            $sql = 'SELECT
                        sbas_id as databox_id,
                        server_coll_id as collection_id,
                        base_id
                    FROM bas';
            $statement = $connection->query($sql);

            $map = array();
            while ($mapping = $statement->fetch()) {
                $map = igorw\assoc_in($map, [$mapping['databox_id'], $mapping['collection_id']], (int) $mapping['base_id']);
            }

            $this->collectionMap = $map;
        }

        return $this->collectionMap;
    }

    public static function validateDate($date)
    {
        $d = DateTime::createFromFormat(Mapping::DATE_FORMAT_CAPTION_PHP, $date);
        return $d && $d->format(Mapping::DATE_FORMAT_CAPTION_PHP) == $date;
    }

    public static function sanitizeDate($value)
    {
        // introduced in https://github.com/alchemy-fr/Phraseanet/commit/775ce804e0257d3a06e4e068bd17330a79eb8370#diff-bee690ed259e0cf73a31dee5295d2edcR286
        // not sure if it's really needed
        try {
            $date = new \DateTime($value);
            return $date->format(Mapping::DATE_FORMAT_CAPTION_PHP);
        } catch (\Exception $e) {
            return null;
        }
    }
}
