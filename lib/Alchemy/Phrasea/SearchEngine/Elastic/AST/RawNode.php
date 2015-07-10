<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;

class RawNode extends Node
{
    private $text;

    public static function createFromEscaped($escaped)
    {
        $unescaped = str_replace(
            ['\\\\', '\\"'],
            ['\\', '"'],
            $escaped
        );

        return new self($unescaped);
    }

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function buildQuery(QueryContext $context)
    {
        $query_builder = function (array $fields) {
            $query = [];
            if (count($fields) > 1) {
                $query['multi_match']['query'] = $this->text;
                $query['multi_match']['fields'] = $fields;
                $query['multi_match']['analyzer'] = 'keyword';
            } else {
                $field = reset($fields);
                $query['term'][$field] = $this->text;
            }

            return $query;
        };

        $query = $query_builder($context->getRawFields());
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
        return sprintf('<raw:"%s">', $this->text);
    }
}
