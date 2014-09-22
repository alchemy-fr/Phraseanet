<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class KeywordNode extends Node
{
    protected $keyword;

    public function __construct($keyword)
    {
        $this->keyword = $keyword;
    }

    public function getValue()
    {
        return $this->keyword;
    }

    public function getQuery()
    {
        throw new LogicException("A keyword can't be converted to a query.");
    }

    public function __toString()
    {
        return sprintf('<%s>', $this->keyword);
    }
}
