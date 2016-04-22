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

use Alchemy\Phrasea\Hydration\Hydrator;
use Assert\Assertion;

class MediaSubdefRepository
{
    /**
     * @var MediaSubdefDataRepository
     */
    private $repository;

    /**
     * @var \media_subdef[]
     */
    private $idMap = [];

    /**
     * @var callable
     */
    private $subdefFactory;

    /**
     * @var Hydrator
     */
    private $hydrator;

    public function __construct(MediaSubdefDataRepository $repository, callable $subdefFactory, Hydrator $hydrator = null)
    {
        $this->repository = $repository;
        $this->subdefFactory = $subdefFactory;
        $this->hydrator = $hydrator ?: new MediaSubdefHydrator();
    }

    /**
     * @param int $recordId
     * @param string $name
     * @return \media_subdef|null
     */
    public function findOneByRecordIdAndName($recordId, $name)
    {
        $subdefs = $this->repository->findByRecordIdsAndNames([$recordId], [$name]);

        if (!$subdefs) {
            return null;
        }

        $instances = $this->hydrateAll($subdefs);

        return reset($instances);
    }

    /**
     * @param int[] $recordIds
     * @param string[] $names
     * @return \media_subdef[]
     */
    public function findByRecordIdsAndNames(array $recordIds, array $names = null)
    {
        if (!$recordIds) {
            return [];
        }

        $data = $this->repository->findByRecordIdsAndNames($recordIds, $names);

        return $this->hydrateAll($data);
    }

    /**
     * @param \media_subdef|\media_subdef[] $subdefs
     */
    public function save($subdefs)
    {
        $data = array_map([$this->hydrator, 'extract'], $this->normalizeToArray($subdefs));

        $this->repository->save($data);
    }

    /**
     * @param \media_subdef|\media_subdef[] $subdefs
     */
    public function delete($subdefs)
    {
        $subdefIds = array_map(function (\media_subdef $subdef) {
            return [
                'record_id' => $subdef->get_record_id(),
                'name' => $subdef->get_name(),
            ];
        }, $this->normalizeToArray($subdefs));

        $this->repository->delete($subdefIds);
    }

    public function clear()
    {
        $this->idMap = [];
    }

    /**
     * @param string $index
     * @param array $data
     * @return \media_subdef
     */
    private function hydrate($index, array $data)
    {
        if (isset($this->idMap[$index])) {
            $this->hydrator->hydrate($this->idMap[$index], $data);

            return $this->idMap[$index];
        }

        $factory = $this->subdefFactory;

        $instance = $factory($data);
        Assertion::isInstanceOf($instance, \media_subdef::class);

        $this->idMap[$index] = $instance;

        return $instance;
    }

    /**
     * @param array $data
     * @return \media_subdef[]
     */
    private function hydrateAll(array $data)
    {
        $instances = [];

        foreach ($data as $item) {
            $instances[] = $this->hydrate(json_encode([$item['record_id'], $item['name']]), $item);
        }

        return $instances;
    }

    /**
     * @param \media_subdef|\media_subdef[] $subdefs
     * @return array
     */
    private function normalizeToArray($subdefs)
    {
        if (!is_array($subdefs) || !$subdefs instanceof \Traversable) {
            $subdefs = [$subdefs];
        } elseif ($subdefs instanceof \Traversable) {
            $subdefs = iterator_to_array($subdefs);
        }
        Assertion::allIsInstanceOf($subdefs, \media_subdef::class);

        return $subdefs;
    }
}
