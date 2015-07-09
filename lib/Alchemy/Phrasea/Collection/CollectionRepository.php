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

}
