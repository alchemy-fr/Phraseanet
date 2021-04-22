<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\Visitor\Element;
use Hoa\Visitor\Visit;
use InvalidArgumentException;

class QueryVisitor implements Visit
{
    private $structure;

    public function __construct(Structure $structure)
    {
        $this->structure = $structure;
    }

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

            case NodeTypes::FLAG_STATEMENT:
                return $this->visitFlagStatementNode($element);

            case NodeTypes::FLAG:
                return new AST\Flag($this->visitString($element));

            case NodeTypes::MATCH_EXPR:
                return $this->visitMatchExpressionNode($element);

            case NodeTypes::NATIVE_KEY:
                return $this->visitNativeKeyNode($element);

            case NodeTypes::TIMESTAMP_KEY:
                return $this->visitTimestampKeyNode($element);

            case NodeTypes::GEOLOCATION_KEY:
                return $this->visitGeolocationKeyNode($element);

            case NodeTypes::METADATA_KEY:
                return new AST\KeyValue\MetadataKey($this->visitString($element));

            case NodeTypes::FIELD_KEY:
                return new AST\KeyValue\FieldKey($this->visitString($element));

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
        return $this->handleBinaryExpression($node, function($left, $right) {
            return new AST\FieldMatchExpression($left, $right);
        });
    }

    private function visitAndNode(Element $element)
    {
        return $this->handleBinaryExpression($element, function($left, $right) {
            return new AST\Boolean\AndExpression($left, $right);
        });
    }

    private function visitOrNode(Element $element)
    {
        return $this->handleBinaryExpression($element, function($left, $right) {
            return new AST\Boolean\OrExpression($left, $right);
        });
    }

    private function visitExceptNode(Element $element)
    {
        return $this->handleBinaryExpression($element, function($left, $right) {
            return new AST\Boolean\ExceptExpression($left, $right);
        });
    }

    private function visitRangeNode(TreeNode $node)
    {
        $this->assertChildrenCount($node, 2);
        $key = $node->getChild(0)->accept($this);
        $boundary = $node->getChild(1)->accept($this);

        if ($this->isDateKey($key)) {
            if(($v = RecordHelper::sanitizeDate($boundary)) !== null) {
                $boundary = $v;
            }
        }

        switch ($node->getId()) {
            case NodeTypes::LT_EXPR:
                return AST\KeyValue\RangeExpression::lessThan($key, $boundary);
            case NodeTypes::LTE_EXPR:
                return AST\KeyValue\RangeExpression::lessThanOrEqual($key, $boundary);
            case NodeTypes::GT_EXPR:
                return AST\KeyValue\RangeExpression::greaterThan($key, $boundary);
            case NodeTypes::GTE_EXPR:
                return AST\KeyValue\RangeExpression::greaterThanOrEqual($key, $boundary);
        }
    }

    private function handleBinaryExpression(Element $element, \Closure $factory)
    {
        $this->assertChildrenCount($element, 2);

        $left  = $element->getChild(0)->accept($this);
        $right = $element->getChild(1)->accept($this);

        return $factory($left, $right);
    }

    private function visitEqualNode(TreeNode $node)
    {
        return $this->handleBinaryExpression($node, function($left, $right) {
            if($right === AST\KeyValue\MissingExpression::MISSING_VALUE) {
                return new AST\KeyValue\MissingExpression($left);
            }

            if($right === AST\KeyValue\ExistsExpression::EXISTS_VALUE) {
                return new AST\KeyValue\ExistsExpression($left);
            }

            if ($this->isDateKey($left)) {
                try {
                    // Try to create a range for incomplete dates
                    $range = QueryHelper::getRangeFromDateString($right);
                    if ($range['from'] === $range['to']) {
                        return new AST\KeyValue\EqualExpression($left, $range['from']);
                    }
                    else {
                        return new AST\KeyValue\RangeExpression(
                            $left,
                            $range['from'], true,
                            $range['to'], false
                        );
                    }
                }
                catch (\InvalidArgumentException $e) {
                    // Fall back to equal expression
                }
            }

            return new AST\KeyValue\EqualExpression($left, $right);

        });
    }

    private function isDateKey(AST\KeyValue\Key $key)
    {
        if ($key instanceof AST\KeyValue\TimestampKey) {
            return true;
        } elseif ($key instanceof AST\KeyValue\FieldKey) {
            return $this->structure->typeOf($key->getName()) === FieldMapping::TYPE_DATE;
        }
        return false;
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
                $root = new AST\Boolean\AndExpression($root, $node);
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
        $this->assertChildrenCount($node, 2);
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

    private function visitMatchExpressionNode(TreeNode $node)
    {
        return $this->handleBinaryExpression($node, function($left, $right) {
            return new AST\KeyValue\MatchExpression($left, $right);
        });
    }

    private function visitGeolocationKeyNode(TreeNode $node)
    {
        return AST\KeyValue\GeolocationKey::geolocation();
    }

    private function visitNativeKeyNode(TreeNode $node)
    {
        $this->assertChildrenCount($node, 1);
        $type = $node->getChild(0)->getValue()['token'];
        switch ($type) {
            case NodeTypes::TOKEN_DATABASE:
                return AST\KeyValue\NativeKey::database();
            case NodeTypes::TOKEN_COLLECTION:
                return AST\KeyValue\NativeKey::collection();
            case NodeTypes::TOKEN_SHA256:
                return AST\KeyValue\NativeKey::sha256();
            case NodeTypes::TOKEN_UUID:
                return AST\KeyValue\NativeKey::uuid();
            case NodeTypes::TOKEN_MEDIA_TYPE:
                return AST\KeyValue\NativeKey::mediaType();
            case NodeTypes::TOKEN_RECORD_ID:
                return AST\KeyValue\NativeKey::recordIdentifier();
            default:
                throw new InvalidArgumentException(sprintf('Unexpected token type "%s" for native key.', $type));
        }
    }

    private function visitTimestampKeyNode(TreeNode $node)
    {
        $this->assertChildrenCount($node, 1);
        $type = $node->getChild(0)->getValue()['token'];
        switch ($type) {
            case NodeTypes::TOKEN_CREATED_ON:
                return AST\KeyValue\TimestampKey::createdOn();
            case NodeTypes::TOKEN_UPDATED_ON:
                return AST\KeyValue\TimestampKey::updatedOn();
            default:
                throw new InvalidArgumentException(sprintf('Unexpected token type "%s" for timestamp key.', $type));
        }
    }

    private function assertChildrenCount(TreeNode $node, $count)
    {
        if ($node->getChildrenNumber() !== $count) {
            throw new Exception(sprintf('Node was expected to have only %s children.', $count));
        }
    }
}
