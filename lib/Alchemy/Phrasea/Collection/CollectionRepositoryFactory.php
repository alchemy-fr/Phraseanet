<?php

namespace Alchemy\Phrasea\Collection;

interface CollectionRepositoryFactory
{
    /**
     * @param int $databoxId
     * @return CollectionRepository
     */
    public function createRepositoryForDatabox($databoxId);
}
