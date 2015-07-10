<?php

namespace Alchemy\Phrasea\Collection;

interface CollectionRepository
{

    /**
     * @return \collection[]
     */
    public function findAll();

    /**
     * @param int $collectionId
     * @return \collection|null
     */
    public function find($collectionId);

    /**
     * @param Collection $collection
     * @return void
     */
    public function save(Collection $collection);

    /**
     * @param Collection $collection
     * @return void
     */
    public function delete(Collection $collection);
}
