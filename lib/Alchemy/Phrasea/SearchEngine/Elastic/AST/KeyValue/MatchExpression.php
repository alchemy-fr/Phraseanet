<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Node;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Assert\Assertion;

class MatchExpression extends Node
{
    private $key;
    private $value;

    public function __construct(Key $key, $value)
    {
        Assertion::string($value);
        $this->key = $key;
        $this->value = $value;
    }

    public function buildQuery(QueryContext $context)
    {
        if (!$this->key->isValueCompatible($this->value, $context)) {
            throw new QueryException(sprintf('Value "%s" for key "%s" is not valid.', $this->value, $this->key));
        }

        return [
            'match' => [
                $this->key->getIndexField($context) => $this->value
            ]
        ];
    }

    public function getTermNodes()
    {
        return [];
    }

    public function __toString()
    {
        return sprintf('<%s:"%s">', $this->key, $this->value);
    }
}
