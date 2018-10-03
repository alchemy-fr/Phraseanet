<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

interface QueryPostProcessor
{
    public function postProcessQuery($query, QueryContext $context);
}
