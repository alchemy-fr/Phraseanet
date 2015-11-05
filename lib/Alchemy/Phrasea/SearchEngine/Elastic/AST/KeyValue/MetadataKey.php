<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Assert\Assertion;

class MetadataKey
{
    private $name;

    public function __construct($name)
    {
        Assertion::string($name);
        $this->name = $name;
    }

    public function __toString()
    {
        return $this->name;
    }
}
