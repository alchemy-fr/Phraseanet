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

    public function getIndexField()
    {
        return sprintf('exif.%s', $this->name);
    }

    public function __toString()
    {
        return sprintf('metadata.%s', $this->name);
    }
}
