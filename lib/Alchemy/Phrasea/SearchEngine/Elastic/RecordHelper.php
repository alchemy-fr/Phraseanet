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

}
