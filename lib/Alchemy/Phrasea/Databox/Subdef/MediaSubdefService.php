<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Subdef;

use Alchemy\Phrasea\Databox\DataboxBoundRepositoryProvider;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Record\RecordReferenceCollection;

class MediaSubdefService
{
    /**
     * @var DataboxBoundRepositoryProvider
     */
    private $repositoryProvider;

    public function __construct(DataboxBoundRepositoryProvider $repositoryProvider)
    {
        $this->repositoryProvider = $repositoryProvider;
    }

    /**
     * Returns all available subdefs grouped by each record reference and by its name
     *
     * @param RecordReferenceInterface[]|RecordReferenceCollection $records
     * @return \media_subdef[][]
     */
    public function findSubdefsByRecordReferenceFromCollection($records)
    {
        $subdefs = $this->reduceRecordReferenceCollection(
            $records,
            function (array &$carry, array $subdefs, array $indexes) {
                /** @var \media_subdef $subdef */
                foreach ($subdefs as $subdef) {
                    $index = $indexes[$subdef->get_record_id()];

                    $carry[$index][$subdef->get_name()] = $subdef;
                }
            },
            array_fill_keys(array_keys(iterator_to_array($records)), [])
        );

        ksort($subdefs);

        return $subdefs;
    }

    /**
     * @param RecordReferenceInterface[]|RecordReferenceCollection $records
     * @return \media_subdef[]
     */
    public function findSubdefsFromRecordReferenceCollection($records)
    {
        $groups = $this->reduceRecordReferenceCollection(
            $records,
            function (array &$carry, array $subdefs) {
                $carry[] = $subdefs;

                return $carry;
            },
            []
        );

        if ($groups) {
            return call_user_func_array('array_merge', $groups);
        }

        return [];
    }

    /**
     * @param RecordReferenceInterface[]|RecordReferenceCollection $records
     * @param callable $process
     * @param mixed $initialValue
     * @return mixed
     */
    private function reduceRecordReferenceCollection($records, callable $process, $initialValue)
    {
        $records = $this->normalizeRecordCollection($records);

        $carry = $initialValue;

        foreach ($records->groupPerDataboxId() as $databoxId => $indexes) {
            $subdefs = $this->getRepositoryForDatabox($databoxId)->findByRecordIdsAndNames(array_keys($indexes));

            $carry = $process($carry, $subdefs, $indexes, $databoxId);
        }

        return $carry;
    }

    /**
     * @param int $databoxId
     * @return MediaSubdefRepository
     */
    private function getRepositoryForDatabox($databoxId)
    {
        return $this->repositoryProvider->getRepositoryForDatabox($databoxId);
    }

    /**
     * @param RecordReferenceInterface[]|RecordReferenceCollection $records
     * @return RecordReferenceCollection
     */
    private function normalizeRecordCollection($records)
    {
        if ($records instanceof RecordReferenceCollection) {
            return $records;
        }

        return new RecordReferenceCollection($records);
    }
}
