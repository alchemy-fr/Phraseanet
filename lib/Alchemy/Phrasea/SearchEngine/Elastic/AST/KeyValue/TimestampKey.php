<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Typed;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\ValueChecker;

class TimestampKey implements Key, Typed
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

    public function getType()
    {
        return FieldMapping::TYPE_DATE;
    }

    public function getIndexField(QueryContext $context, $raw = false)
    {
        return $this->index_field;
    }

    public function isValueCompatible($value, QueryContext $context)
    {
        return ValueChecker::isValueCompatible($this, $value);
    }

    public function __toString()
    {
        return $this->type;
    }
}
