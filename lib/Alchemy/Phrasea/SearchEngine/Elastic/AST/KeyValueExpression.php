<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Assert\Assertion;

class KeyValueExpression extends Node
{
    protected $key;
    protected $value;

    public function __construct(Key $key, $value)
    {
        Assertion::string($value);
        $this->key = $key;
        $this->value = $value;
    }

    public function buildQuery(QueryContext $context)
    {
        return [
            'term' => [
                $this->key->getIndexField() => $this->value
            ]
        ];
    }

    public function getTermNodes()
    {
        return [];
    }

    public function __toString()
    {
        return sprintf('<%s:%s>', $this->key, $this->value);
    }
}
