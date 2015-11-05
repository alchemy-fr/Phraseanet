<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
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

            case NodeTypes::FIELD_STATEMENT:
                return $this->visitFieldStatementNode($element);

            case NodeTypes::FIELD:
                return new AST\Field($this->visitString($element));

            case NodeTypes::METADATA_STATEMENT:
                return $this->visitMetadataStatementNode($element);

            case NodeTypes::METADATA_KEY:
                return $this->visitMetadataKeyNode($element);

            case NodeTypes::FLAG_STATEMENT:
                return $this->visitFlagStatementNode($element);

            case NodeTypes::FLAG:
                return new AST\Flag($this->visitString($element));

            case NodeTypes::NATIVE_KEY_VALUE:
                return $this->visitNativeKeyValueNode($element);

            case NodeTypes::NATIVE_KEY:
                return $this->visitNativeKeyNode($element);

            default:
                throw new Exception(sprintf('Unknown node type "%s".', $element->getId()));
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

    private function visitFieldStatementNode(TreeNode $node)
    {
        if ($node->getChildrenNumber() !== 2) {
            throw new Exception('Field statement must have 2 childs.');
        }
        $field = $this->visit($node->getChild(0));
        $value = $this->visit($node->getChild(1));
        return new AST\FieldMatchExpression($field, $value);
    }

    private function visitAndNode(Element $element)
    {
        return $this->handleBinaryOperator($element, function($left, $right) {
            return new AST\Boolean\AndOperator($left, $right);
        });
    }

    private function visitOrNode(Element $element)
    {
        return $this->handleBinaryOperator($element, function($left, $right) {
            return new AST\Boolean\OrOperator($left, $right);
        });
    }

    private function visitExceptNode(Element $element)
    {
        return $this->handleBinaryOperator($element, function($left, $right) {
            return new AST\Boolean\ExceptOperator($left, $right);
        });
    }

    private function visitRangeNode(TreeNode $node)
    {
        if ($node->getChildrenNumber() !== 2) {
            throw new Exception('Comparison operator can only have 2 childs.');
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
            throw new Exception('Binary expression can only have 2 childs.');
        }
        $left  = $element->getChild(0)->accept($this);
        $right = $element->getChild(1)->accept($this);

        return $factory($left, $right);
    }

    private function visitEqualNode(TreeNode $node)
    {
        if ($node->getChildrenNumber() !== 2) {
            throw new Exception('Equality operator can only have 2 childs.');
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
                    throw new Exception('Unexpected text node after context');
                }
                $words[] = $node->getValue();
            } elseif ($node instanceof AST\Context) {
                $context = $node;
            } else {
                throw new Exception('Term node can only contain text nodes');
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
                    throw new Exception('Unexpected text node after context');
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
                    throw new Exception('Unexpected context after non-contextualizable node');
                }
            } elseif ($node instanceof AST\Node) {
                $root = new AST\Boolean\AndOperator($root, $node);
            } else {
                throw new Exception('Unexpected node type inside text node.');
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

    private function visitFlagStatementNode(TreeNode $node)
    {
        if ($node->getChildrenNumber() !== 2) {
            throw new Exception('Flag statement can only have 2 childs.');
        }
        $flag = $node->getChild(0)->accept($this);
        if (!$flag instanceof AST\Flag) {
            throw new \Exception('Flag statement key must be a flag node.');
        }

        return new AST\FlagStatement(
            $flag->getName(),
            $this->visitBoolean($node->getChild(1))
        );
    }

    private function visitBoolean(TreeNode $node)
    {
        if (null === $value = $node->getValue()) {
            throw new Exception('Boolean node must be a token');
        }
        switch ($value['token']) {
            case NodeTypes::TOKEN_TRUE:
                return true;

            case NodeTypes::TOKEN_FALSE:
                return false;

            default:
                throw new Exception('Unexpected token for a boolean.');
        }
    }

    private function visitMetadataKeyNode(TreeNode $node)
    {
        $name = $this->visitString($node);
        if (!QueryHelper::isValidMetadataName($name)) {
            throw new QueryException(sprintf('"%s" is not a valid metadata name', $name));
        }
        return new AST\KeyValue\MetadataKey($name);
    }

    private function visitMetadataStatementNode(TreeNode $node)
    {
        if ($node->getChildrenNumber() !== 2) {
            throw new Exception('Flag statement can only have 2 childs.');
        }
        $name = $this->visit($node->getChild(0));
        $value = $this->visit($node->getChild(1));
        return new AST\MetadataMatchStatement($name, $value);
    }

    private function visitNativeKeyValueNode(TreeNode $node)
    {
        if ($node->getChildrenNumber() !== 2) {
            throw new Exception('Key value expression can only have 2 childs.');
        }
        $key = $this->visit($node->getChild(0));
        $value = $this->visit($node->getChild(1));
        return new AST\KeyValue\Expression($key, $value);
    }

    private function visitNativeKeyNode(Element $element)
    {
        if ($element->getChildrenNumber() !== 1) {
            throw new Exception('Native key node can only have a single child.');
        }
        $type = $element->getChild(0)->getValue()['token'];
        switch ($type) {
            case NodeTypes::TOKEN_DATABASE:
                return AST\KeyValue\NativeKey::database();
            case NodeTypes::TOKEN_COLLECTION:
                return AST\KeyValue\NativeKey::collection();
            case NodeTypes::TOKEN_MEDIA_TYPE:
                return AST\KeyValue\NativeKey::mediaType();
            case NodeTypes::TOKEN_RECORD_ID:
                return AST\KeyValue\NativeKey::recordIdentifier();
            default:
                throw new InvalidArgumentException(sprintf('Unexpected token type "%s" for native key.', $type));
        }
    }
}
