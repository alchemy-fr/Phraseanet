<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermInterface;

class TextNode extends AbstractTermNode
{
    public function buildQuery(QueryContext $context)
    {
        $query = array(
            'multi_match' => array(
                'fields'   => $context->getLocalizedFields(),
                'query'    => $this->text,
                'operator' => 'and',
            )
        );

        if ($conceptQueries = $this->buildConceptQueries($context)) {
            $textQuery = $query;
            $query = array();
            $query['bool']['should'] = $conceptQueries;
            $query['bool']['should'][] = $textQuery;
        }

        return $query;
    }

    public function __toString()
    {
        return sprintf('<text:%s>', Term::dump($this));
    }
}
