<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class InExpression extends Node
{
    protected $field;
    protected $expression;

    public function __construct(FieldNode $field, $expression)
    {
        $this->field = $field;
        $this->expression = $expression;
    }

    public function buildQuery(QueryContext $context)
    {
        $fields = array($this->field->getValue());

        return $this->expression->buildQuery($context->narrowToFields($fields));
    }

    public function getTermNodes()
    {
        return $this->expression->getTermNodes();
    }

    public function __toString()
    {
        return sprintf('(%s IN %s)', $this->expression, $this->field);
    }
}
