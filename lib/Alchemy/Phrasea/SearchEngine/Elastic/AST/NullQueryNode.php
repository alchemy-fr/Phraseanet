<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class NullQueryNode extends Node
{
    public function buildQuery(QueryContext $context)
    {
        return [
            'match_all' => (object)null
        ];
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
