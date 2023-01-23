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

    public function getTermNodes()
    {
        return $this->root->getTermNodes();
    }

    public function build(QueryContext $context)
    {
        $query = $this->root->buildQuery($context);
        if ($query === null) {
            //$query = ['bool'=> ['must' => []]];
            // a null query shoud return no results !
            $query = ['constant_score'=> ['filter' => new \stdClass]];
        }

        return $query;
    }

    public function dump()
    {
        return $this->root->__toString();
    }
}
