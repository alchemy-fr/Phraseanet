<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class TimestampKey implements Key
{
    private $type;
    private $index_field;

    public static function createdOn()
    {
        return new self('creation', 'created_on');
    }

    public static function updatedOn()
    {
        return new self('update', 'updated_on');
    }

    private function __construct($type, $index_field)
    {
        $this->type = $type;
        $this->index_field = $index_field;
    }

    public function getIndexField(QueryContext $context, $raw = false)
    {
        return $this->index_field;
    }

    public function isValueCompatible($value, QueryContext $context)
    {
        return true;
    }

    public function __toString()
    {
        return $this->type;
    }
}
