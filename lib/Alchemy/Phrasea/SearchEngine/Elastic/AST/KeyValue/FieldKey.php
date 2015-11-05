<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Assert\Assertion;

class FieldKey implements Key
{
    private $name;

    public function __construct($name)
    {
        Assertion::string($name);
        $this->name = $name;
    }

    public function getIndexField()
    {
        return 'yolo';
    }

    public function getValue()
    {
        return $this->name;
    }

    public function __toString()
    {
        return sprintf('field.%s', $this->name);
    }
}
