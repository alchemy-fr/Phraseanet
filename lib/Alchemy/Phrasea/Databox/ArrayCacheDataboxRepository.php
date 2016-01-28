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
        $this->clear();

        return $this->repository->save($databox);
    }

    /**
     * @param \databox $databox
     */
    public function delete(\databox $databox)
    {
        $this->clear();

        return $this->repository->delete($databox);
    }

    /**
     * @param \databox $databox
     */
    public function unmount(\databox $databox)
    {
        $this->clear();

        return $this->repository->unmount($databox);
    }

    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $dbname
     *
     * @return \databox
     */
    public function mount($host, $port, $user, $password, $dbname)
    {
        $this->clear();

        return $this->repository->mount($host, $port, $user, $password, $dbname);
    }

    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $dbname
     *
     * @return \databox
     */
    public function create($host, $port, $user, $password, $dbname)
    {
        $this->clear();

        return $this->repository->create($host, $port, $user, $password, $dbname);
    }

    /**
     * Initializes the memory cache if needed.
     */
    private function load()
    {
        if (! $this->loaded) {
            $this->databoxes = $this->repository->findAll();
            $this->loaded = true;
        }
    }

    /**
     * Clears the memory cache.
     */
    private function clear()
    {
        $this->loaded = false;
        $this->databoxes = [];
    }
}
