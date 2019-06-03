<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\ValueChecker;

class RawNode extends Node
{
    private $text;
    private $index_fields_callback;

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
            $index_fields = [];
            foreach ($fields as $field) {
                $index_fields[] = $field->getIndexField(true);
            }
            $query = null;
            if (count($index_fields) > 1) {
                $query = [
                    'multi_match' => [
                        'query'    => $this->text,
                        'fields'   => $index_fields,
                        'analyzer' => 'keyword'
                    ]
                ];
            } elseif (count($index_fields) === 1) {
                $index_field = reset($index_fields);
                $query = [
                    'term' => [
                        $index_field => $this->text
                    ]
                ];
            }

            return $query;
        };

        $unrestricted_fields = $context->getUnrestrictedFields();
        $unrestricted_fields = ValueChecker::filterByValueCompatibility($unrestricted_fields, $this->text);
        $query = $query_builder($unrestricted_fields);

        $private_fields = $context->getPrivateFields();
        $private_fields = ValueChecker::filterByValueCompatibility($private_fields, $this->text);
        foreach (QueryHelper::wrapPrivateFieldQueries($private_fields, $unrestricted_fields, $query_builder) as $private_field_query) {
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
