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
            $this->getRepositoryForDatabox($databoxId)->findByRecordIds(array_keys($indexes));
        }

        if ($groups) {
            return call_user_func_array('array_merge', $groups);
        }

        return [];
    }

    /**
     * @param RecordReferenceInterface[]|RecordReferenceCollection $references
     * @return RecordReferenceCollection
     */
    public function normalizeReferenceCollection($references)
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
}
