<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class FieldNode extends Node
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

    public function buildQuery(QueryContext $context)
    {
        throw new \LogicException("A keyword can't be converted to a query.");
    }

    public function getTextNodes()
    {
        throw new \LogicException("A keyword can't contain text nodes.");
    }

    public function __toString()
    {
        return sprintf('<field:%s>', $this->keyword);
    }
}
