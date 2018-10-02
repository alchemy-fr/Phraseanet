<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;

class TermNode extends AbstractTermNode
{
    public function buildQuery(QueryContext $context)
    {
        $query_builder = function (array $fields) {
            $concept_queries = $this->buildConceptQueries($fields);
            $query = null;
            foreach ($concept_queries as $concept_query) {
                $query = QueryHelper::applyBooleanClause($query, 'should', $concept_query);
            }
            return $query;
        };

        $query = $query_builder($context->getUnrestrictedFields());
        $private_fields = $context->getPrivateFields();
        foreach (QueryHelper::wrapPrivateFieldQueries($private_fields, $query_builder) as $concept_query) {
            $query = QueryHelper::applyBooleanClause($query, 'should', $concept_query);
        }

        return $query;
    }

    public function __toString()
    {
        return sprintf('<term:%s>', Term::dump($this));
    }
}
