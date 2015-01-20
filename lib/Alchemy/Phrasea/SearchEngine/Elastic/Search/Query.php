<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Node;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\NullQueryNode;
use Hoa\Compiler\Llk\TreeNode;

class Query
{
    private $root;

    public function __construct(Node $root = null)
    {
        if (!$root) {
            $root = new NullQueryNode();
        }
        $this->root = $root;
    }

    public function getTextNodes()
    {
        return $this->root->getTextNodes();
    }

    public function build(QueryContext $context)
    {
        return $this->root->buildQuery($context);
    }

    public function dump()
    {
        return $this->root->__toString();
    }
}
