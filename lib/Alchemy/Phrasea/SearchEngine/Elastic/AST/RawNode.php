<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field as StructureField;

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

        $fields = $context->getRawFields();
        $query = count($fields) ? $query_builder($fields) : null;

        foreach (QueryHelper::buildPrivateFieldQueries($context, $query_builder, $this->getIndexFieldsCallback()) as $private_field_query) {
            $query = QueryHelper::applyBooleanClause($query, 'should', $private_field_query);
        }

        return $query;
    }

    private function getIndexFieldsCallback()
    {
        if ($this->index_fields_callback === null) {
            $this->index_fields_callback = function (StructureField $field) {
                return $field->getIndexField(true);
            };
        }

        return $this->index_fields_callback;
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
