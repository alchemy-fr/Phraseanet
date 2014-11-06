<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class InExpression extends Node
{
    protected $field;
    protected $expression;

    public function __construct(FieldNode $field, $expression)
    {
        $this->field = $field;
        $this->expression = $expression;
    }

    public function getQuery()
    {
        return $this->expression->getQuery($this->field->getValue());
    }

    public function __toString()
    {
        return sprintf('(%s IN %s)', $this->expression, $this->field);
    }

    public function isFullTextOnly()
    {
        // In expressions are never full-text
        return false;
    }
}
