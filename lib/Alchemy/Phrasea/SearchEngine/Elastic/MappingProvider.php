<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic;

interface MappingProvider
{
    /**
     * @return Mapping
     */
    public function getMapping();
}
