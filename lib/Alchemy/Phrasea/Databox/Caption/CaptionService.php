<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Caption;

use Alchemy\Phrasea\Databox\DataboxBoundRepositoryProvider;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Record\RecordReferenceCollection;

class CaptionService
{
    /**
     * @var DataboxBoundRepositoryProvider
     */
    private $repositoryProvider;

    public function __construct(DataboxBoundRepositoryProvider $repositoryProvider)
    {
        $this->repositoryProvider = $repositoryProvider;
    }

    public function findByReferenceCollection($references)
    {
        $references = $this->normalizeReferenceCollection($references);

        $groups = [];

        foreach ($references->groupPerDataboxId() as $databoxId => $indexes) {
            $captions = $this->getRepositoryForDatabox($databoxId)->findByRecordIds(array_keys($indexes));

            $groups[$databoxId] = array_combine($indexes, $captions);
        }

        return $this->reorderInstances($references, $groups);
    }

    /**
     * @param RecordReferenceInterface[]|RecordReferenceCollection $references
     * @return RecordReferenceCollection
     */
    private function normalizeReferenceCollection($references)
    {
        if ($references instanceof RecordReferenceCollection) {
            return $references;
        }

        return new RecordReferenceCollection($references);
    }

    /**
     * @param int $databoxId
     * @return CaptionRepository
     */
    private function getRepositoryForDatabox($databoxId)
    {
        return $this->repositoryProvider->getRepositoryForDatabox($databoxId);
    }

    /**
     * @param RecordReferenceCollection $references
     * @param \caption_record[][] $groups
     * @return \caption_record[]
     */
    private function reorderInstances(RecordReferenceCollection $references, array $groups)
    {
        $captions = [];

        foreach ($references as $index => $reference) {
            $captions[$index] = $groups[$reference->getDataboxId()][$index];
        }

        return $captions;
    }
}
