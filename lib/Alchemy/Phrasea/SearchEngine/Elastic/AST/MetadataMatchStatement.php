<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Assert\Assertion;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\MetadataKey;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;

class MetadataMatchStatement extends Node
{
    private $key;
    private $value;

    public function __construct(MetadataKey $key, $value)
    {
        Assertion::string($value);
        $this->key = $key;
        $this->value = $value;
    }

    public function buildQuery(QueryContext $context)
    {
        $field = sprintf('exif.%s', $this->key);
        return [
            'term' => [
                $field => $this->value
            ]
        ];
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<metadata:%s value:"%s">', $this->key, $this->value);
    }
}
