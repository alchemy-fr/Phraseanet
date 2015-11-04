<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Assert\Assertion;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;

class MetadataMatchStatement extends Node
{
    private $name;
    private $value;

    public function __construct($name, $value)
    {
        Assertion::string($name);
        Assertion::string($value);
        $this->name = $name;
        $this->value = $value;
    }

    public function buildQuery(QueryContext $context)
    {
        if (!QueryHelper::isValidMetadataName($this->name)) {
            throw new QueryException(sprintf('"%s" is not a valid metadata name', $this->name));
        }
        $field = sprintf('exif.%s', $this->name);
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
        return sprintf('<metadata:%s value:"%s">', $this->name, $this->value);
    }
}
