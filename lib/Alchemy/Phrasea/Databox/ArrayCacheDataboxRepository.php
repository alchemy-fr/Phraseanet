<?php

namespace Alchemy\Phrasea\Databox;

class ArrayCacheDataboxRepository implements DataboxRepository
{
    /**
     * @var DataboxRepository
     */
    private $repository;

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var \databox[]
     */
    private $databoxes = [];

    /**
     * @param DataboxRepository $repository
     */
    public function __construct(DataboxRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param int $id
     * @return \databox
     */
    public function find($id)
    {
        $this->load();

        if (! isset($this->databoxes[$id])) {
            return null;
        }

        return $this->databoxes[$id];
    }

    /**
     * @return \databox[]
     */
    public function findAll()
    {
        $this->load();

        return $this->databoxes;
    }

    /**
     * @param \databox $databox
     */
    public function save(\databox $databox)
    {
        $this->loaded = false;
        $this->databoxes = [];

        return $this->repository->save($databox);
    }

    private function load()
    {
        if (! $this->loaded) {
            $this->databoxes = $this->repository->findAll();
            $this->loaded = true;
        }
    }
}
