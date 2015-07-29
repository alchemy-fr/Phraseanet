<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class CollectionExpression extends Node
{
    private $collectionName;

    public function __construct($collectionName)
    {
        $this->collectionName = $collectionName;
    }

    public function buildQuery(QueryContext $context)
    {
        return [
            'term' => [
                'collection_name' => $this->collectionName
            ]
        ];
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<collection:%s>', $this->collectionName);
    }
}
