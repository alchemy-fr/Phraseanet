<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;

class TermNode extends AbstractTermNode
{
    public function buildQuery(QueryContext $context)
    {
        $query = array();
        $query['bool']['should'] = $this->buildConceptQueries($context);

        return $query;
    }

    public function __toString()
    {
        return sprintf('<term:%s>', Term::dump($this));
    }
}
