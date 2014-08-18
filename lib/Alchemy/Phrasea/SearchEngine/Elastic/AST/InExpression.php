<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class InExpression extends Node
{
    protected $keyword;
    protected $expression;

    public function __construct(KeywordNode $keyword, $expression)
    {
        $this->keyword = $keyword;
        $this->expression = $expression;
    }

    public function getQuery()
    {
        return $this->expression->getQuery($this->keyword->getValue());
    }

    public function __toString()
    {
        return sprintf('(%s IN %s)', $this->expression, $this->keyword);
    }
}
