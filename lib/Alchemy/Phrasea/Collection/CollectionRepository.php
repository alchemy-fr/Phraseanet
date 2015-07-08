<?php

namespace Alchemy\Phrasea\Collection;

interface CollectionRepository 
{

    /**
     * @param int $databoxId
     * @return \collection[]
     */
    public function findAllByDatabox($databoxId);

    /**
     * @param int $baseId
     * @return \collection|null
     */
    public function find($baseId);

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return \collection|null
     */
    public function findByCollectionId($databoxId, $collectionId);
}
