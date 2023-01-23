<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Key;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\FieldKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\MetadataKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\NativeKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\RangeExpression;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Prophecy\Argument;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class RangeExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testSerializability()
    {
        $this->assertTrue(method_exists(RangeExpression::class, '__toString'), 'Class does not have method __toString');
    }

    /**
     * @dataProvider serializationProvider
     */
    public function testSerialization($expression, $serialization)
    {
        $this->assertEquals($serialization, (string) $expression);
    }

    public function serializationProvider()
    {
        $key_prophecy = $this->prophesize(Key::class);
        $key_prophecy->__toString()->willReturn('foo');
        $key = $key_prophecy->reveal();
        return [
            [RangeExpression::lessThan($key, 42),           '<range:foo lt="42">' ],
            [RangeExpression::lessThanOrEqual($key, 42),    '<range:foo lte="42">'],
            [RangeExpression::greaterThan($key, 42),        '<range:foo gt="42">' ],
            [RangeExpression::greaterThanOrEqual($key, 42), '<range:foo gte="42">'],
        ];
    }

    /**
     * @dataProvider queryProvider
     */
    public function testQueryBuild($factory, $query_context, $key, $value, $result)
    {
        $node = RangeExpression::$factory($key, $value);
        $query = $node->buildQuery($query_context);
        $this->assertEquals(json_decode($result, true), $query);
    }

    public function queryProvider()
    {
        $query_context = $this->prophesize(QueryContext::class)->reveal();
        $key_prophecy = $this->prophesize(Key::class);
        $key_prophecy->getFieldType($query_context)->willReturn('text');
        $key_prophecy->getIndexField($query_context)->willReturn('foo');
        $key_prophecy->isValueCompatible('bar', $query_context)->willReturn(true);
        $key = $key_prophecy->reveal();
        return [
            ['lessThan',           $query_context, $key, 'bar', '{"range":{"foo": {"lt":"bar"}}}'],
            ['lessThanOrEqual',    $query_context, $key, 'bar', '{"range":{"foo": {"lte":"bar"}}}'],
            ['greaterThan',        $query_context, $key, 'bar', '{"range":{"foo": {"gt":"bar"}}}'],
            ['greaterThanOrEqual', $query_context, $key, 'bar', '{"range":{"foo": {"gte":"bar"}}}'],
        ];
    }

    public function testQueryBuildWithFieldKey()
    {
        $query_context = $this->prophesize(QueryContext::class)->reveal();
        $key = $this->prophesize(FieldKey::class);
        $key->getFieldType($query_context)->willReturn('text');
        $key->getIndexField($query_context)->willReturn('baz');
        $key->isValueCompatible('bar', $query_context)->willReturn(true);
        $key->postProcessQuery(Argument::any(), $query_context)->willReturnArgument(0);

        $node = RangeExpression::lessThan($key->reveal(), 'bar');
        $query = $node->buildQuery($query_context);

        $expected = '{
            "range": {
                "baz": {
                    "lt": "bar"
                }
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
