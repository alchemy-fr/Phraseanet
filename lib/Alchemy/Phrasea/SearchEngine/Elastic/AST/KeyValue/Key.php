<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

interface Key
{
    public function buildQueryForValue($value, QueryContext $context);
    public function __toString();
}
