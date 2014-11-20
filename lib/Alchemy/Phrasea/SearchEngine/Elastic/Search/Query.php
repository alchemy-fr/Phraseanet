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

    public function getElasticsearchQuery()
    {
        return $this->root->getQuery();
    }

    public function dump()
    {
        return $this->root->__toString();
    }
}
