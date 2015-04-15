<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class Field
{
    protected $field;

    public function __construct($field)
    {
        $this->field = $field;
    }

    public function getValue()
    {
        return $this->field;
    }

    public function __toString()
    {
        return sprintf('<field:%s>', $this->field);
    }
}
