<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class FieldEqualsExpression extends Node
{
    private $field;
    private $value;

    public function __construct(Field $field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function buildQuery(QueryContext $context)
    {
        $field = $context->normalizeField($this->field->getValue());
        $query = array();
        $query['term'][$field] = $this->value;

        return $query;
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('(%s == <value:"%s">)', $this->field, $this->value);
    }
}
