<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Node;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryPostProcessor;

class MissingExpression extends Node
{
    const MISSING_VALUE = '_unset_';

    private $key;
    private $value;

    public function __construct(Key $key)
    {
        $this->key = $key;
    }

    public function buildQuery(QueryContext $context)
    {
        $query = [
            'bool' => [
                'must_not' => [
                    'exists' => [
                        'field' => $this->key->getIndexField($context, true)
                    ]
                ]
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
        return sprintf('(<%s> == <%s>)', $this->key, self::MISSING_VALUE);
    }
}
