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

    public function visit(Element $element, &$handle = null, $eldnah = null)
    {
        if (null !== $value = $element->getValue()) {
            switch ($value['token']) {
                case self::NODE_TOKEN_WORD:
                    return new AST\TextNode($value['value']);

                case self::NODE_TOKEN_STRING:
                    return new AST\QuotedTextNode($value['value']);

                default:
                    // Generic handling off other tokens for unresctricted text
                    return new AST\TextNode($value['value']);
            }
        }

        switch ($element->getId()) {
            case self::NODE_TYPE_QUERY:
                $root = null;
                foreach ($element->getChildren() as $child) {
                    $node = $child->accept($this, $handle, $eldnah);
                    if ($root) {
                        $root = new AST\AndExpression($root, $node);
                    } else {
                        $root = $node;
                    }
                }
                return new Query($root);

            case self::NODE_TYPE_IN_EXPR:
                if ($element->getChildrenNumber() !== 2) {
                    throw new \Exception('IN expression can only have 2 childs.');
                }
                $expression = $element->getChild(0)->accept($this);
                $field = new AST\FieldNode($element->getChild(1)->getValue()['value']);
                return new AST\InExpression($field, $expression);

            case self::NODE_TYPE_AND_EXPR:
                if ($element->getChildrenNumber() !== 2) {
                    throw new \Exception('AND expression can only have 2 childs.');
                }
                $left  = $element->getChild(0)->accept($this);
                $right = $element->getChild(1)->accept($this);
                return new AST\AndExpression($left, $right);

            case self::NODE_TYPE_OR_EXPR:
                if ($element->getChildrenNumber() !== 2) {
                    throw new \Exception('OR expression can only have 2 childs.');
                }
                $left  = $element->getChild(0)->accept($this);
                $right = $element->getChild(1)->accept($this);
                return new AST\OrExpression($left, $right);

            case self::NODE_TYPE_TEXT:
            case self::NODE_TYPE_UNRESTRICTED_TEXT:
                $root = null;
                foreach ($element->getChildren() as $child) {
                    $node = $child->accept($this, $handle, $eldnah);
                    if ($root) {
                        // $root = new AST\AndExpression($root, $node);
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

            default:
                throw new \Exception(sprintf('Unknown node type "%s".', $element->getId()));
        }
    }
}
