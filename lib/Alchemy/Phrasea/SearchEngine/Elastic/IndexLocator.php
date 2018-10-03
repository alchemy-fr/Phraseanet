<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndex;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndex;

class IndexLocator
{
    /**
     * @var \ArrayAccess
     */
    private $container;
    /**
     * @var
     */
    private $recordIndexKey;
    /**
     * @var
     */
    private $termIndexKey;

    /**
     * @param \ArrayAccess $container
     * @param string $recordIndexKey
     * @param string $termIndexKey
     */
    public function __construct(\ArrayAccess $container, $recordIndexKey, $termIndexKey)
    {
        $this->container = $container;
        $this->recordIndexKey = $recordIndexKey;
        $this->termIndexKey = $termIndexKey;
    }

    /**
     * @return TermIndex
     */
    public function getTermIndex()
    {
        return $this->container[$this->termIndexKey];
    }

    /**
     * @return RecordIndex
     */
    public function getRecordIndex()
    {
        return $this->container[$this->recordIndexKey];
    }
}
