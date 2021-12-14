<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Node;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryPostProcessor;

class ExistsExpression extends Node
{
    const EXISTS_VALUE = '_set_';

    private $key;
    private $value;

    public function __construct(Key $key)
    {
        $this->key = $key;
    }

    public function buildQuery(QueryContext $context)
    {
        $query = [
            'exists' => [
                'field' => $this->key->getIndexField($context, true)
            ]
        ];

        if ($this->key instanceof QueryPostProcessor) {
            return $this->key->postProcessQuery($query, $context);
        }

        return $query;
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('(<%s> == <%s>)', $this->key, self::EXISTS_VALUE);
    }
}
