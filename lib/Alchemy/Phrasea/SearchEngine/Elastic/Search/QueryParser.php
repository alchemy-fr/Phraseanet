<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Hoa\Compiler\Exception\Exception as CompilerException;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Compiler\Visitor\Dump as DumpVisitor;
use Hoa\Visitor\Visit;

class QueryParser
{
    private $parser;

    private static $leftAssociativeOperators = array(
        NodeTypes::AND_EXPR,
        NodeTypes::OR_EXPR,
        NodeTypes::EXCEPT_EXPR
    );

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Creates a Query object from a string
     */
    public function parse($string)
    {
        return $this->visitString($string, new QueryVisitor());
    }

    public function dump($string)
    {
        return $this->visitString($string, new DumpVisitor());
    }

    private function visitString($string, Visit $visitor)
    {
        try {
            $ast = $this->parser->parse($string);
        } catch (CompilerException $e) {
            throw new QueryException('Provided query is not valid', 0, $e);
        }


        $this->fixOperatorAssociativity($ast);

        return $visitor->visit($ast);
    }

    /**
     * Walks the tree to restore left-associativity of some operators
     *
     * @param  TreeNode $root AST root node
     */
    private function fixOperatorAssociativity(TreeNode &$root)
    {
        switch ($root->getChildrenNumber()) {
            case 0:
                // Leaf nodes can't be rotated, and have no childs
                return;

            case 2:
                // We only want to rotate tree contained in the left associative
                // subset of operators
                $rootType = $root->getId();
                if (!in_array($rootType, self::$leftAssociativeOperators)) {
                    break;
                }
                // Do not break operator precedence
                $pivot = $root->getChild(1);
                if ($pivot->getId() !== $rootType) {
                    break;
                }
                $this->leftRotateTree($root);
                break;
        }

        // Recursively fix tree branches
        $children = $root->getChildren();
        foreach ($children as $index => $_) {
            $this->fixOperatorAssociativity($children[$index]);
        }
        $root->setChildren($children);
    }

    private function leftRotateTree(TreeNode &$root)
    {
        // Pivot = Root.Left
        $pivot = $root->getChild(1);
        // Root.Right = Pivot.Left
        $children = $root->getChildren();
        $children[1] = $pivot->getChild(0); // Pivot, rotation side
        $root->setChildren($children);
        // Pivot.Left = Root
        $children = $pivot->getChildren();
        $children[0] = $root;
        $pivot->setChildren($children);
        // Root = Pivot
        $root = $pivot;
    }
}

