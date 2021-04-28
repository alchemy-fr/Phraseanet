<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use stdClass;

class NullQueryNode extends Node
{
    public function buildQuery(QueryContext $context)
    {
        return array('match_all' => new stdClass());
    }

    public function getTermNodes()
    {
        return [];
    }

    public function __toString()
    {
        return '<NULL>';
    }
}
