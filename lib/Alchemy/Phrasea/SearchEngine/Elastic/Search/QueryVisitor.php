<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Visitor\Element;
use Hoa\Visitor\Visit;
use InvalidArgumentException;

class QueryVisitor implements Visit
{
    public function visit(Element $element, &$handle = null, $eldnah = null)
    {
        if (null !== $value = $element->getValue()) {
            return $this->visitToken($value['token'], $value['value']);
        }

        return $this->visitNode($element);
    }

    private function visitToken($token, $value)
    {
        switch ($token) {
            case NodeTypes::TOKEN_WORD:
                return new AST\TextNode($value);

            case NodeTypes::TOKEN_QUOTED_STRING:
                return new AST\QuotedTextNode($value);

            case NodeTypes::TOKEN_RAW_STRING:
                return AST\RawNode::createFromEscaped($value);

            default:
                // Generic handling off other tokens for unresctricted text
                return new AST\TextNode($value);
        }
    }

    private function visitNode(Element $element)
    {
        switch ($element->getId()) {
            case NodeTypes::QUERY:
                return $this->visitQuery($element);

            case NodeTypes::GROUP:
                return $this->visitNode($element->getChild(0));

            case NodeTypes::IN_EXPR:
                return $this->visitInNode($element);

            case NodeTypes::AND_EXPR:
                return $this->visitAndNode($element);

            case NodeTypes::OR_EXPR:
                return $this->visitOrNode($element);

            case NodeTypes::EXCEPT_EXPR:
                return $this->visitExceptNode($element);

            case NodeTypes::LT_EXPR:
            case NodeTypes::GT_EXPR:
            case NodeTypes::LTE_EXPR:
            case NodeTypes::GTE_EXPR:
                return $this->visitRangeNode($element);

            case NodeTypes::EQUAL_EXPR:
                return $this->visitEqualNode($element);

            case NodeTypes::VALUE:
                return $this->visitString($element);

            case NodeTypes::TERM:
                return $this->visitTerm($element);

            case NodeTypes::TEXT:
                return $this->visitText($element);

            case NodeTypes::CONTEXT:
                return new AST\Context($this->visitString($element));

            case NodeTypes::FIELD:
                return new AST\Field($this->visitString($element));

            case NodeTypes::DATABASE:
                return $this->visitDatabaseNode($element);

            case NodeTypes::COLLECTION:
                return $this->visitCollectionNode($element);

            case NodeTypes::TYPE:
                return $this->visitTypeNode($element);

            case NodeTypes::IDENTIFIER:
                return $this->visitIdentifierNode($element);

            default:
                throw new \Exception(sprintf('Unknown node type "%s".', $element->getId()));
        }
    }

    private function visitQuery(Element $element)
    {
        $root = null;
        foreach ($element->getChildren() as $child) {
            $root = $child->accept($this);
        }
        return new Query($root);
    }

    private function visitInNode(Element $element)
    {
        if ($element->getChildrenNumber() !== 2) {
            throw new \Exception('IN expression can only have 2 childs.');
        }
        $expression = $element->getChild(0)->accept($this);
        $field = $this->visit($element->getChild(1));
        return new AST\InExpression($field, $expression);
    }

    private function visitAndNode(Element $element)
    {
        return $this->handleBinaryOperator($element, function($left, $right) {
            return new AST\AndExpression($left, $right);
        });
    }

    private function visitOrNode(Element $element)
    {
        return $this->handleBinaryOperator($element, function($left, $right) {
            return new AST\OrExpression($left, $right);
        });
    }

    private function visitExceptNode(Element $element)
    {
        return $this->handleBinaryOperator($element, function($left, $right) {
            return new AST\ExceptExpression($left, $right);
        });
    }

    private function visitRangeNode(TreeNode $node)
    {
        if ($node->getChildrenNumber() !== 2) {
            throw new \Exception('Comparison operator can only have 2 childs.');
        }
        $field = $node->getChild(0)->accept($this);
        $expression = $node->getChild(1)->accept($this);

        switch ($node->getId()) {
            case NodeTypes::LT_EXPR:
                return AST\RangeExpression::lessThan($field, $expression);
            case NodeTypes::LTE_EXPR:
                return AST\RangeExpression::lessThanOrEqual($field, $expression);
            case NodeTypes::GT_EXPR:
                return AST\RangeExpression::greaterThan($field, $expression);
            case NodeTypes::GTE_EXPR:
                return AST\RangeExpression::greaterThanOrEqual($field, $expression);
        }
    }

    private function handleBinaryOperator(Element $element, \Closure $factory)
    {
        if ($element->getChildrenNumber() !== 2) {
            throw new \Exception('Binary expression can only have 2 childs.');
        }
        $left  = $element->getChild(0)->accept($this);
        $right = $element->getChild(1)->accept($this);

        return $factory($left, $right);
    }

    private function visitEqualNode(TreeNode $node)
    {
        if ($node->getChildrenNumber() !== 2) {
            throw new \Exception('Equality operator can only have 2 childs.');
        }

        return new AST\FieldEqualsExpression(
            $node->getChild(0)->accept($this),
            $node->getChild(1)->accept($this)
        );
    }

    private function visitTerm(Element $element)
    {
        $words = array();
        $context = null;
        foreach ($element->getChildren() as $child) {
            $node = $child->accept($this);
            if ($node instanceof AST\TextNode) {
                if ($context) {
                    throw new \Exception('Unexpected text node after context');
                }
                $words[] = $node->getValue();
            } elseif ($node instanceof AST\Context) {
                $context = $node;
            } else {
                throw new \Exception('Term node can only contain text nodes');
            }
        }

        return new AST\TermNode(implode($words), $context);
    }

    private function visitText(Element $element)
    {
        $nodes = array();

        // Walk childs and merge adjacent text nodes if needed
        $last = null;
        $last_index = -1;
        foreach ($element->getChildren() as $child) {
            $node = $child->accept($this);
            // Merge text nodes together
            if ($last instanceof AST\TextNode &&
                $node instanceof AST\TextNode) {
                // Prevent merge once a context is set
                if ($last->hasContext()) {
                    throw new \Exception('Unexpected text node after context');
                }
                $nodes[$last_index] = $last = AST\TextNode::merge($last, $node);
            } else {
                $nodes[] = $node;
                $last = $node;
                $last_index++;
            }
        }

        // Once nodes are merged, we just "AND" them all together.
        $root = null;
        foreach ($nodes as $index => $node) {
            if (!$root) {
                $root = $node;
                continue;
            }
            if ($node instanceof AST\Context) {
                if ($root instanceof AST\ContextAbleInterface) {
                    $root = $root->withContext($node);
                } else {
                    throw new \Exception('Unexpected context after non-contextualizable node');
                }
            } elseif ($node instanceof AST\Node) {
                $root = new AST\AndExpression($root, $node);
            }
        }

        return $root;
    }

    private function visitString(TreeNode $node)
    {
        $tokens = array();
        foreach ($node->getChildren() as $child) {
            $value = $child->getValue();
            if ($value === null || !isset($value['value'])) {
                throw new InvalidArgumentException(sprintf('A token node was expected, got "%s".', $child->getId()));
            }
            $tokens[] = $value['value'];
        }

        return implode($tokens);
    }

    private function visitDatabaseNode(Element $element)
    {
        if ($element->getChildrenNumber() !== 1) {
            throw new \Exception('Base filter can only have a single child.');
        }
        $baseName = $element->getChild(0)->getValue()['value'];

        return new AST\DatabaseExpression($baseName);
    }

    private function visitCollectionNode(Element $element)
    {
        if ($element->getChildrenNumber() !== 1) {
            throw new \Exception('Collection filter can only have a single child.');
        }
        $collectionName = $element->getChild(0)->getValue()['value'];

        return new AST\CollectionExpression($collectionName);
    }

    private function visitTypeNode(Element $element)
    {
        if ($element->getChildrenNumber() !== 1) {
            throw new \Exception('Type filter can only have a single child.');
        }
        $typeName = $element->getChild(0)->getValue()['value'];

        return new AST\TypeExpression($typeName);
    }

    private function visitIdentifierNode(Element $element)
    {
        if ($element->getChildrenNumber() !== 1) {
            throw new \Exception('Identifier filter can only have a single child.');
        }
        $identifier = $element->getChild(0)->getValue()['value'];

        return new AST\RecordIdentifierExpression($identifier);
    }
}
