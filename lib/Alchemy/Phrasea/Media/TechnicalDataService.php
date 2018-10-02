<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

use Alchemy\Phrasea\Databox\DataboxGroupable;
use Alchemy\Phrasea\Record\PerDataboxRecordId;
use Alchemy\Phrasea\Record\RecordReference;
use Alchemy\Phrasea\Record\RecordReferenceCollection;

class TechnicalDataService
{
    /**
     * @var RecordTechnicalDataSetRepositoryProvider
     */
    private $provider;

    public function __construct(RecordTechnicalDataSetRepositoryProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param DataboxGroupable|PerDataboxRecordId|RecordReference[] $references
     * @return RecordTechnicalDataSet[]
     */
    public function fetchRecordsTechnicalData($references)
    {
        if (!($references instanceof DataboxGroupable && $references instanceof PerDataboxRecordId)) {
            $references = new RecordReferenceCollection($references);
        }

        $sets = [];

        foreach ($references->getDataboxIds() as $databoxId) {
            $recordIds = $references->getDataboxRecordIds($databoxId);

            $setPerRecordId = [];

            foreach ($this->provider->getRepositoryFor($databoxId)->findByRecordIds($recordIds) as $set) {
                $setPerRecordId[$set->getRecordId()] = $set;
            }

            $sets[$databoxId] = $setPerRecordId;
        }

        $reorder = [];

        foreach ($references as $index => $reference) {
            $databoxId = $reference->getDataboxId();
            $recordId = $reference->getRecordId();

            $reorder[$index] = isset($sets[$databoxId][$recordId])
                ? $sets[$databoxId][$recordId]
                : new RecordTechnicalDataSet($recordId);
        }

        return $reorder;
    }
}
