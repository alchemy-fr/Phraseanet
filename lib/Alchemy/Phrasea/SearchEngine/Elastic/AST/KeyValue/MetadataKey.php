<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Assert\Assertion;

class MetadataKey implements Key
{
    private $name;

    public function __construct($name)
    {
        Assertion::string($name);
        $this->name = $name;
    }

    public function getIndexField(QueryContext $context)
    {
        return sprintf('exif.%s', $this->name);
    }

    public function isValueCompatible($value, QueryContext $context)
    {
        return true;
    }

    public function __toString()
    {
        return sprintf('metadata.%s', $this->name);
    }
}
