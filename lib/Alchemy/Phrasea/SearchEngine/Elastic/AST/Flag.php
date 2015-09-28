<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Assert\Assertion;

class Flag
{
    private $name;

    public function __construct($name)
    {
        Assertion::string($name);
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
