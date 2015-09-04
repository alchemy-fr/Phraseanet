<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class TypeExpression extends Node
{
    private $typeName;

    public function __construct($typeName)
    {
        $this->typeName = $typeName;
    }

    public function buildQuery(QueryContext $context)
    {
        return [
            'term' => [
                'type' => $this->typeName
            ]
        ];
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<type:%s>', $this->typeName);
    }
}
