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
     * @param null|array $names
     * @return \media_subdef[][]
     */
    public function findSubdefsByRecordReferenceFromCollection($records, array $names = null)
    {
        $subdefs = $this->reduceRecordReferenceCollection(
            $records,
            function (array &$carry, array $subdefs, array $references) {
                $subdefsByRecordId = [];

                /** @var \media_subdef $subdef */
                foreach ($subdefs as $subdef) {
                    $recordId = $subdef->get_record_id();

                    if (!isset($subdefsByRecordId[$recordId])) {
                        $subdefsByRecordId[$recordId] = [];
                    }

                    $subdefsByRecordId[$recordId][$subdef->get_name()] = $subdef;
                }

                /** @var RecordReferenceInterface $reference */
                foreach ($references as $index => $reference) {
                    if (isset($subdefsByRecordId[$reference->getRecordId()])) {
                        $carry[$index] = $subdefsByRecordId[$reference->getRecordId()];
                    };
                }

                return $carry;
            },
            array_fill_keys(array_keys($records instanceof \Traversable ? iterator_to_array($records) : $records), []),
            $names
        );

        $reordered = [];

        foreach ($records as $index => $record) {
            $reordered[$index] = $subdefs[$index];
        }

        return $reordered;
    }

    /**
     * @param RecordReferenceInterface[]|RecordReferenceCollection $records
     * @param null|string[] $names
     * @return \media_subdef[]
     */
    public function findSubdefsFromRecordReferenceCollection($records, array $names = null)
    {
        $groups = $this->reduceRecordReferenceCollection(
            $records,
            function (array &$carry, array $subdefs) {
                $carry[] = $subdefs;

                return $carry;
            },
            [],
            $names
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
     * @param null|string[] $names
     * @return mixed
     */
    private function reduceRecordReferenceCollection($records, callable $process, $initialValue, array $names = null)
    {
        $records = $this->normalizeRecordCollection($records);

        $carry = $initialValue;

        foreach ($records->getDataboxIds() as $databoxId) {
            $recordIds = $records->getDataboxRecordIds($databoxId);

            $subdefs = $this->getRepositoryForDatabox($databoxId)
                ->findByRecordIdsAndNames($recordIds, $names);

            $carry = $process($carry, $subdefs, $records->getDataboxGroup($databoxId), $databoxId);
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
