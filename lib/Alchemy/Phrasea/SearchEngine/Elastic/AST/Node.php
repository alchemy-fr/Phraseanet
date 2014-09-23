<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

abstract class Node
{
    /**
     * @return array The Elasticsearch formatted query
     */
    abstract public function getQuery();

    /**
     * @return bool  Tell if the node and it's child are full-text queries only
     */
    abstract public function isFullTextOnly();
}
