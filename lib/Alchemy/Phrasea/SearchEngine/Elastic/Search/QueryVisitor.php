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

            case NodeTypes::IN_EXPR:
                return $this->visitInNode($element);

            case NodeTypes::AND_EXPR:
                return $this->visitAndNode($element);

            case NodeTypes::OR_EXPR:
                return $this->visitOrNode($element);

            case NodeTypes::EXCEPT_EXPR:
                return $this->visitExceptNode($element);

            case NodeTypes::TEXT:
                return $this->visitText($element);

            default:
                throw new \Exception(sprintf('Unknown node type "%s".', $element->getId()));
        }
    }

    private function visitQuery(Element $element)
    {
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

    private function visitText(Element $element)
    {
        $root = null;
        foreach ($element->getChildren() as $child) {
            $node = $child->accept($this);
            if ($root) {
                // Merge text nodes together, but not with quoted ones
                if ($root instanceof AST\TextNode &&
                    !$root instanceof AST\QuotedTextNode &&
                    !$node instanceof AST\QuotedTextNode) {
                    $root = new AST\TextNode(sprintf('%s %s', $root->getText(), $node->getText()));
                } else {
                    $root = new AST\AndExpression($root, $node);
                }
            } else {
                $root = $node;
            }
        }

        return $root;
    }
}
