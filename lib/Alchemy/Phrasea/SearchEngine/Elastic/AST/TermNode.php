<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class TermNode extends TextNode
{
    public function buildQuery(QueryContext $context)
    {
        $query = array();
        $query['bool']['should'] = $this->buildConceptQueries($context);

        return $query;
    }

    public function __toString()
    {
        return sprintf('<term:%s>', $this->text);
    }
}
