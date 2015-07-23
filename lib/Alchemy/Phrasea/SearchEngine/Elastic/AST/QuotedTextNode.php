<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\TextQueryHelper;

class QuotedTextNode extends Node
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function buildQuery(QueryContext $context)
    {
        $query_builder = function (array $fields) use ($context) {
            $index_fields = [];
            foreach ($fields as $field) {
                foreach ($context->localizeField($field) as $index_fields[]);
            }
            if (!$index_fields) {
                return null;
            }
            return [
                'multi_match' => [
                    'type'   => 'phrase',
                    'fields' => $index_fields,
                    'query'  => $this->text,
                ]
            ];
        };

        $query = $query_builder($context->getUnrestrictedFields());
        $private_fields = $context->getPrivateFields();
        $private_fields = TextQueryHelper::filterCompatibleFields($private_fields, $this->text);
        foreach (QueryHelper::wrapPrivateFieldQueries($private_fields, $query_builder) as $private_field_query) {
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
