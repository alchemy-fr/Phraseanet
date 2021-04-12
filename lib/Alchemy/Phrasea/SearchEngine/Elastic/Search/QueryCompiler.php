<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Hoa\Compiler\Exception\Exception as CompilerException;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Compiler\Visitor\Dump as DumpVisitor;
use Hoa\Visitor\Visit;

class QueryCompiler
{
    private $parser;
    private $queryVisitorFactory;
    private $thesaurus;

    private static $leftAssociativeOperators = array(
        NodeTypes::AND_EXPR,
        NodeTypes::OR_EXPR,
        NodeTypes::EXCEPT_EXPR
    );

    public function __construct(Parser $parser, callable $queryVisitorFactory, Thesaurus $thesaurus)
    {
        $this->parser = $parser;
        $this->queryVisitorFactory = $queryVisitorFactory;
        $this->thesaurus = $thesaurus;
    }

    public function compile($string, QueryContext $context)
    {
        $query = $this->parse($string);
        $this->injectThesaurusConcepts($query, $context);

        return $query->build($context);
    }

    /**
     * @param Query $query
     * @param QueryContext $context
     */
    private function injectThesaurusConcepts(Query $query, $context)
    {
        // TODO We must restrict thesaurus matching for IN queries, and only
        // search in each field's root concepts.
        $nodes = $query->getTermNodes();
        $filter = Thesaurus\Filter::byDataboxes($context->getDataboxes());
        $concepts = $this->thesaurus->findConceptsBulk($nodes, null, $filter, false);

        foreach ($concepts as $index => $termConcepts) {
            $node = $nodes[$index];
            $node->setConcepts($termConcepts);
        }
    }

    /**
     * Creates a Query object from a string
     */
    public function parse($string, $postprocessing = true)
    {
        return $this->visitString($string, $this->createQueryVisitor(), $postprocessing);
    }

    /**
     * @return Visit
     */
    private function createQueryVisitor()
    {
        return call_user_func($this->queryVisitorFactory);
    }

    public function dump($string, $postprocessing = true)
    {
        return $this->visitString($string, new DumpVisitor(), $postprocessing);
    }

    private function visitString($string, Visit $visitor, $postprocessing = true)
    {
        try {
            $ast = $this->parser->parse($string);
        } catch (CompilerException $e) {
            throw new QueryException('Provided query is not valid', 0, $e);
        }

        if ($postprocessing) {
            $this->fixOperatorAssociativity($ast);
        }

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

