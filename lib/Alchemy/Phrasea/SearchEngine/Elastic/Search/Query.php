<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Node;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\AndOperator;
use Hoa\Compiler\Llk\TreeNode;

class Query
{
    private $root;

    public function __construct(Node $root)
    {
        $this->root = $root;
    }

    /*
     * This method seems weird to me, the implementation returns true when the
     * query doesn't contain IN statements, but that doesn't define a full text
     * search.
     */
    public function isFullTextOnly()
    {
        return $this->root->isFullTextOnly();
    }

    public function getElasticsearchQuery($fields = array())
    {
        return $this->root->getQuery($fields);
    }

    public function dump()
    {
        return $this->root->__toString();
    }
}
