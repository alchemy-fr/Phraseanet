<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

abstract class Node
{
    /**
     * @return array The Elasticsearch formatted query
     */
    abstract public function buildQuery(QueryContext $context);

    abstract public function getTermNodes();
}
