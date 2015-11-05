<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Key;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\FieldKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\MetadataKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\NativeKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\RangeExpression;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

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
    public function testQueryBuild($factory, $key, $value, $result)
    {
        $query_context = $this->prophesize(QueryContext::class);
        $node = RangeExpression::$factory($key, $value);
        $query = $node->buildQuery($query_context->reveal());
        $this->assertEquals(json_decode($result, true), $query);
    }

    public function queryProvider()
    {
        $key_prophecy = $this->prophesize(Key::class);
        $key_prophecy->getIndexField()->willReturn('foo');
        $key = $key_prophecy->reveal();
        return [
            ['lessThan',           $key, 'bar', '{"range":{"foo": {"lt":"bar"}}}'],
            ['lessThanOrEqual',    $key, 'baz', '{"range":{"foo": {"lte":"baz"}}}'],
            ['greaterThan',        $key, 'qux', '{"range":{"foo": {"gt":"qux"}}}'],
            ['greaterThanOrEqual', $key, 'bla', '{"range":{"foo": {"gte":"bla"}}}'],
        ];
    }

    public function testQueryBuildWithFieldKey()
    {
        $key = $this->prophesize(FieldKey::class);
        $key->getValue()->willReturn('foo');
        $node = RangeExpression::lessThan($key->reveal(), 'bar');
        $structure_field = $this->prophesize(Field::class);
        $structure_field->isPrivate()->willReturn(false);
        $structure_field->isValueCompatible('bar')->willReturn(true);
        $structure_field->getIndexField()->willReturn('baz');
        $query_context = $this->prophesize(QueryContext::class);
        $query_context->get('foo')->willReturn($structure_field->reveal());
        $query = $node->buildQuery($query_context->reveal());

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
