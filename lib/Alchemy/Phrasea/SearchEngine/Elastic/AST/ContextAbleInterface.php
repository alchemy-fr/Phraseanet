<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;

interface ContextAbleInterface
{
    /**
     * Return a new object with the same content and the provided context.
     *
     * @param Context $context Context to add on the new object
     */
    public function withContext(Context $context);
}
