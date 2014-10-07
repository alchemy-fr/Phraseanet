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
use igorw;

class RecordHelper
{
    private $connection;

    private $collectionMap;

    public function __construct(appbox $appbox)
    {
        $this->connection = $appbox->get_connection();
    }

    public function getUniqueRecordId($databoxId, $recordId)
    {
        return sprintf('%d_%d', $databoxId, $recordId);
    }

    public function getUniqueCollectionId($databoxId, $collectionId)
    {
        $col = $this->collectionMap();

        if (isset($col[$databoxId])) {
            $index = array_search($collectionId, $col[$databoxId]);
            if ($index !== false) {
                return (int) $index;
            }
        }

        return null;
    }

    private function collectionMap()
    {
        if (!$this->collectionMap) {
            $sql = 'SELECT
                        sbas_id as databox_id,
                        server_coll_id as collection_id,
                        base_id
                    FROM bas';
            $statement = $this->connection->query($sql);

            $map = array();
            while ($mapping = $statement->fetch()) {
                $map = igorw\assoc_in($map, [$mapping['databox_id'], $mapping['collection_id']], (int) $mapping['base_id']);
            }

            $this->collectionMap = $map;
        }


        return $this->collectionMap;
    }
}
