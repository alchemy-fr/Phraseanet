<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

interface Key
{
    public function getFieldType(QueryContext $context);
    public function getIndexField(QueryContext $context, $raw = false);
    public function isValueCompatible($value, QueryContext $context);
    public function __toString();
}
