<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;

class QuotedTextNode extends Node
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function buildQuery(QueryContext $context)
    {
        $query_builder = function (array $fields) {
            return [
                'multi_match' => [
                    'type'   => 'phrase',
                    'fields' => $fields,
                    'query'  => $this->text,
                ]
            ];
        };

        $fields = $context->getLocalizedFields();
        $query = $fields ? $query_builder($fields) : null;

        foreach (QueryHelper::buildPrivateFieldQueries($context, $query_builder) as $private_field_query) {
            $query = QueryHelper::applyBooleanClause($query, 'should', $private_field_query);
        }

        return $query;
    }

    public function getTermNodes()
    {
        return [];
    }

    public function __toString()
    {
        return sprintf('<exact_text:"%s">', $this->text);
    }
}
