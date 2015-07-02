<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class BaseExpression extends Node
{
    private $baseName;

    public function __construct($baseName)
    {
        $this->baseName = $baseName;
    }

    public function buildQuery(QueryContext $context)
    {
        $query = array();
        $query['term']['databox_name'] = $this->baseName;

        return $query;
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<base:%s>', $this->baseName);
    }
}
