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
     * @param RecordReference[] $references
     * @return RecordTechnicalDataSet[]
     */
    public function fetchRecordsTechnicalData($references)
    {
        if (!$references instanceof  RecordReferenceCollection) {
            $references = new RecordReferenceCollection($references);
        }

        $sets = [];

        foreach ($references->groupPerDataboxId() as $databoxId => $indexes) {
            foreach ($this->provider->getRepositoryFor($databoxId)->findByRecordIds(array_keys($indexes)) as $set) {
                $index = $indexes[$set->getRecordId()];

                $sets[$index] = $set;
            }
        }

        $reorder = [];

        foreach ($references as $index => $reference) {
            $reorder[$index] = isset($sets[$index]) ? $sets[$index] : new RecordTechnicalDataSet($reference->getRecordId());
        }

        return $reorder;
    }
}
