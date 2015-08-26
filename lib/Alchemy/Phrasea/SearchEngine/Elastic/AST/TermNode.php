<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;

class TermNode extends AbstractTermNode
{
    public function buildQuery(QueryContext $context)
    {
        // Should not match anything if no concept is defined
        // TODO Ensure no match when no concept queries are provided
        return [
            'bool' => [
                'should' => $this->buildConceptQueries($context)
            ]
        ];
    }

    public function __toString()
    {
        return sprintf('<term:%s>', Term::dump($this));
    }
}
