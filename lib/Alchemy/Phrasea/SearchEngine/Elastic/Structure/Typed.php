<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

interface Typed
{
    /**
     * Get the type of the object
     *
     * @return string One of Mapping::TYPE_* constants
     */
    public function getType();
}
