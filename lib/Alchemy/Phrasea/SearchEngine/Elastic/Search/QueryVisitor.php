<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST;
use Hoa\Visitor\Element;
use Hoa\Visitor\Visit;

class QueryVisitor implements Visit
{
    const NODE_TYPE_QUERY    = '#query';
    const NODE_TYPE_IN_EXPR  = '#in';
    const NODE_TYPE_AND_EXPR = '#and';
    const NODE_TYPE_OR_EXPR  = '#or';
    const NODE_TYPE_FIELD    = '#field';
    const NODE_TYPE_TEXT     = '#text';
    const NODE_TYPE_UNRESTRICTED_TEXT = '#unrestricted_text';
    const NODE_TYPE_TOKEN    = 'token';
    const NODE_TOKEN_WORD    = 'word';
    const NODE_TOKEN_STRING  = 'string';
    const NODE_TOKEN_EXCEPT  = 'except';

    private $leftNode;
    private $leftOp;

    public function visit(Element $element, &$handle = null, $eldnah = null)
    {
        if (null !== $value = $element->getValue()) {
            return $this->visitToken($value['token'], $value['value']);
        }

        $node = $this->visitNode($element);
        if ($this->leftOp) {
            $node = $this->leftOp->__invoke($node);
            $this->leftOp = null;
        }
        $this->leftNode = $node;

        return $node;
    }

    private function visitToken($token, $value)
    {
        switch ($token) {
            case self::NODE_TOKEN_WORD:
                return new AST\TextNode($value);

            case self::NODE_TOKEN_STRING:
                return new AST\QuotedTextNode($value);

            case self::NODE_TOKEN_EXCEPT:
                // Schedule the operation at the next node visit using also
                // previous node to build the "except" expression.
                // (we don't have the next node yet).
                //
                // Tokens taking part in an "except" expression are emited by
                // the compiler as a flat list, not a tree, because we can't
                // maintain left-associativity required by EXCEPT operator.
                $left = $this->leftNode;
                $this->leftOp = function ($right) use ($left) {
                    return new AST\ExceptExpression($left, $right);
                };
                break;

            default:
                // Generic handling off other tokens for unresctricted text
                return new AST\TextNode($value);
        }
    }

    private function visitNode(Element $element)
    {
        switch ($element->getId()) {
            case self::NODE_TYPE_QUERY:
                return $this->visitQuery($element);

            case self::NODE_TYPE_IN_EXPR:
                return $this->visitInNode($element);

            case self::NODE_TYPE_AND_EXPR:
                return $this->visitAndNode($element);

            case self::NODE_TYPE_OR_EXPR:
                return $this->visitOrNode($element);

            case self::NODE_TYPE_TEXT:
            case self::NODE_TYPE_UNRESTRICTED_TEXT:
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
