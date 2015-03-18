<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST;
use Hoa\Visitor\Element;
use Hoa\Visitor\Visit;

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

            case NodeTypes::TOKEN_STRING:
                return new AST\QuotedTextNode($value);

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

            case NodeTypes::TERM:
                return $this->visitTerm($element);

            case NodeTypes::TEXT:
                return $this->visitText($element);

            case NodeTypes::CONTEXT:
                return $this->visitContext($element);

            case NodeTypes::COLLECTION:
                return $this->visitCollectionNode($element);

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
        $field = new AST\FieldNode($element->getChild(1)->getValue()['value']);
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

    private function handleBinaryOperator(Element $element, \Closure $factory)
    {
        if ($element->getChildrenNumber() !== 2) {
            throw new \Exception('Binary expression can only have 2 childs.');
        }
        $left  = $element->getChild(0)->accept($this);
        $right = $element->getChild(1)->accept($this);

        return $factory($left, $right);
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

        return new AST\TermNode(implode(' ', $words), $context);
    }

    private function visitContext(Element $element)
    {
        $words = array();
        foreach ($element->getChildren() as $child) {
            $node = $child->accept($this);
            if ($node instanceof AST\TextNode) {
                $words[] = $node->getValue();
            } else {
                throw new \Exception('Context node can only contain text nodes');
            }
        }

        return new AST\Context(implode(' ', $words));
    }

    private function visitText(Element $element)
    {
        $root = null;
        foreach ($element->getChildren() as $child) {
            $node = $child->accept($this);
            if (!$root) {
                $root = $node;
                continue;
            }
            if ($node instanceof AST\Context) {
                $root = new AST\TextNode($root->getValue(), $node);
                continue;
            }
            // Merge text nodes together (quoted nodes do not)
            if ($root instanceof AST\TextNode &&
                $node instanceof AST\TextNode) {
                // Prevent merge once a context is set
                if ($root->hasContext()) {
                    throw new \Exception('Unexpected text node after context');
                }
                $root = new AST\TextNode(sprintf('%s %s', $root->getValue(), $node->getValue()));
            } else {
                $root = new AST\AndExpression($root, $node);
            }
        }

        return $root;
    }

    private function visitCollectionNode(Element $element)
    {
        if ($element->getChildrenNumber() !== 1) {
            throw new \Exception('Collection filter can only have a single child.');
        }
        $collectionName = $element->getChild(0)->getValue()['value'];

        return new AST\CollectionExpression($collectionName);
    }
}
