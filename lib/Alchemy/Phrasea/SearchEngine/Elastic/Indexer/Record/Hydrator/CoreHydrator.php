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

use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;

class CoreHydrator implements HydratorInterface
{
    private $databox_id;
    private $helper;

    public function __construct($databox_id, RecordHelper $helper)
    {
        $this->databox_id = $databox_id;
        $this->helper = $helper;
    }

    public function hydrateRecords(array &$records)
    {
        foreach ($records as &$record) {
            $this->hydrate($record);
        }
    }

    private function hydrate(array &$record)
    {
        // Some casting
        $record['record_id'] = (int) $record['record_id'];
        $record['collection_id'] = (int) $record['collection_id'];
        // Some identifiers
        $record['id'] = $this->helper->getUniqueRecordId($this->databox_id, $record['record_id']);
        $record['base_id'] = $this->helper->getUniqueCollectionId($this->databox_id, $record['collection_id']);
        $record['databox_id'] = $this->databox_id;

        $record['record_type'] = ((int) $record['parent_record_id'] === 1)
            ? SearchEngineInterface::GEM_TYPE_STORY
            : SearchEngineInterface::GEM_TYPE_RECORD;
        unset($record['parent_record_id']);

        if (!$record['mime']) {
            $record['mime'] = 'application/octet-stream';
        }

        return $record;
    }
}
