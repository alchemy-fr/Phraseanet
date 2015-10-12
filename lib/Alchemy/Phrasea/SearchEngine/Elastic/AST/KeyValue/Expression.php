<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Node;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Assert\Assertion;

class Expression extends Node
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
        return $this->key->buildQueryForValue($this->value, $context);
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
