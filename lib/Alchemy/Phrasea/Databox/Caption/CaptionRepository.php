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

class CaptionRepository
{
    /**
     * @var \caption_record[]
     */
    private $idMap = [];

    /**
     * @var CaptionDataRepository
     */
    private $dataRepository;

    /**
     * @var callable
     */
    private $captionFactory;

    public function __construct(CaptionDataRepository $dataRepository, callable $captionFactory)
    {
        $this->dataRepository = $dataRepository;
        $this->captionFactory = $captionFactory;
    }

    public function findByRecordIds(array $recordIds)
    {
        $this->fetchMissing($recordIds);

        $instances = [];

        foreach ($recordIds as $index => $recordId) {
            $instances[$index] = $this->idMap[$recordId];
        }

        return $instances;
    }

    public function clear()
    {
        $this->idMap = [];
    }

    private function fetchMissing(array $recordIds)
    {
        $missing = array_diff($recordIds, array_keys($this->idMap));

        if (!$missing) {
            return;
        }

        $data = $this->dataRepository->findByRecordIds($missing);

        $factory = $this->captionFactory;

        foreach ($data as $recordId => $item) {
            $this->idMap[(int)$recordId] = $factory($recordId, $item);
        }
    }
}
