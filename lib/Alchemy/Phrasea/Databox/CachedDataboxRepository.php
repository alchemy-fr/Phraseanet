<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Cache\Exception;

class CachedDataboxRepository implements DataboxRepositoryInterface
{
    /** @var DataboxRepositoryInterface */
    private $repository;
    /** @var \appbox */
    private $appbox;
    /** @var DataboxHydrator */
    private $hydrator;

    public function __construct(DataboxRepositoryInterface $repository, \appbox $appbox, DataboxHydrator $hydrator)
    {
        $this->repository = $repository;
        $this->appbox = $appbox;
        $this->hydrator = $hydrator;
    }

    public function find($id)
    {
        $rows = $this->fetchRows();

        if (isset($rows[$id])) {
            return $this->hydrator->hydrateRow($id, $rows[$id]);
        }

        return $this->repository->find($id);
    }

    public function findAll()
    {
        $rows = $this->fetchRows();

        if (is_array($rows)) {
            return $this->hydrator->hydrateRows($rows);
        }

        $databoxes = $this->repository->findAll();

        $this->saveRows($databoxes);

        return $databoxes;
    }

    /**
     * @return bool|array false on cache miss
     */
    private function fetchRows()
    {
        try {
            $rows = $this->appbox->get_data_from_cache(\appbox::CACHE_LIST_BASES);
        } catch (Exception $e) {
            $rows = false;
        }

        return $rows;
    }

    /**
     * @param \databox[] $databoxes
     */
    private function saveRows(array $databoxes)
    {
        $rows = array();

        foreach ($databoxes as $databox) {
            $rows[$databox->get_sbas_id()] = $databox->getAsRow();
        }

        $this->appbox->set_data_to_cache($rows, \appbox::CACHE_LIST_BASES);
    }
}
