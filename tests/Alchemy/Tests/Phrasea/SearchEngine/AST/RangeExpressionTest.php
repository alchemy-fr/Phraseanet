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

#[\PHPUnit\Framework\Attributes\Group('unit')]
#[\PHPUnit\Framework\Attributes\Group('searchengine')]
#[\PHPUnit\Framework\Attributes\Group('ast')]
class RangeExpressionTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    public function testSerializability()
    {
        $this->assertTrue(method_exists(RangeExpression::class, '__toString'), 'Class does not have method __toString');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('serializationProvider')]
    public function testSerialization($factory, $serialization)
    {
        $key_prophecy = $this->prophesize(Key::class);
        $key_prophecy->__toString()->willReturn('foo');
        $expression = RangeExpression::$factory($key_prophecy->reveal(), 42);

        $this->assertEquals($serialization, (string) $expression);
    }

    public static function serializationProvider()
    {
        return [
            ['lessThan',           '<range:foo lt="42">' ],
            ['lessThanOrEqual',    '<range:foo lte="42">'],
            ['greaterThan',        '<range:foo gt="42">' ],
            ['greaterThanOrEqual', '<range:foo gte="42">'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('queryProvider')]
    public function testQueryBuild($factory, $value, $result)
    {
        $query_context = $this->prophesize(QueryContext::class)->reveal();
        $key_prophecy = $this->prophesize(Key::class);
        $key_prophecy->getFieldType($query_context)->willReturn('text');
        $key_prophecy->getIndexField($query_context)->willReturn('foo');
        $key_prophecy->isValueCompatible('bar', $query_context)->willReturn(true);

        $node = RangeExpression::$factory($key_prophecy->reveal(), $value);
        $query = $node->buildQuery($query_context);
        $this->assertEquals(json_decode($result, true), $query);
    }

    public static function queryProvider()
    {
        return [
            ['lessThan',           'bar', '{"range":{"foo": {"lt":"bar"}}}'],
            ['lessThanOrEqual',    'bar', '{"range":{"foo": {"lte":"bar"}}}'],
            ['greaterThan',        'bar', '{"range":{"foo": {"gt":"bar"}}}'],
            ['greaterThanOrEqual', 'bar', '{"range":{"foo": {"gte":"bar"}}}'],
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
