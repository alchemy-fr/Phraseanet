<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;

class TermNode extends AbstractTermNode
{
    public function buildQuery(QueryContext $context)
    {
        $query = $this->buildConceptQuery($context);

        // Should not match anything if no concept is defined
        if ($query === null) {
            $query = [
                'bool' => [
                    'should' => []
                ]
            ];
        }

        return $query;
    }

    public function __toString()
    {
        return sprintf('<term:%s>', Term::dump($this));
    }
}
