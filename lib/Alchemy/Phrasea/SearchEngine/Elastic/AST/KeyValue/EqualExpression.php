<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\FieldKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Key;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\Node;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryPostProcessor;

class EqualExpression extends Node
{
    private $key;
    private $value;

    public function __construct(Key $key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function buildQuery(QueryContext $context)
    {
        if (!$this->key->isValueCompatible($this->value, $context)) {
            throw new QueryException(sprintf('Value "%s" for key "%s" is not valid.', $this->value, $this->key));
        }

        if(method_exists($this->key, "buildQuery")) {
            $query = $this->key->buildQuery($this->value, $context);
        }
        else {
            $query = [
                'term' => [
                    $this->key->getIndexField($context, true) => $this->value
                ]
            ];
        }

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
        return sprintf('(<%s> == <value:"%s">)', $this->key, $this->value);
    }
}
