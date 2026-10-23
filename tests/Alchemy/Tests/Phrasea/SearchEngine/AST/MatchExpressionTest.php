<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Key;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\MatchExpression;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\MetadataKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\NativeKey;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class MatchExpressionTest extends \PHPUnit\Framework\TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(MatchExpression::class, '__toString'), 'Class does not have method __toString');
        $key = $this->prophesize(Key::class);
        $key->__toString()->willReturn('foo');
        $node = new MatchExpression($key->reveal(), 'bar');
        $this->assertEquals('<foo:"bar">', (string) $node);
    }

    public function testQueryBuild()
    {
        $query_context = $this->prophesize(QueryContext::class)->reveal();
        $key = $this->prophesize(Key::class);
        $key->isValueCompatible('bar', $query_context)->willReturn(true);
        $key->getIndexField($query_context)->willReturn('foo');
        $node = new MatchExpression($key->reveal(), 'bar');
        $query = $node->buildQuery($query_context);

        $result = '{"match":{"foo": "bar"}}';
        $this->assertEquals(json_decode($result, true), $query);
    }

    /**
     * @dataProvider keyProvider
     */
    public function testNativeQueryBuild($key, $value, $result)
    {
        $query_context = $this->prophesize(QueryContext::class);
        $node = new MatchExpression($key, $value);
        $query = $node->buildQuery($query_context->reveal());
        $this->assertEquals(json_decode($result, true), $query);
    }

    public function keyProvider()
    {
        return [
            [NativeKey::database(),         'foo', '{"match":{"databox_name": "foo"}}'],
            [NativeKey::collection(),       'bar', '{"match":{"collection_name": "bar"}}'],
            [NativeKey::mediaType(),        'baz', '{"match":{"type": "baz"}}'],
            [NativeKey::recordIdentifier(), 'qux', '{"match":{"record_id": "qux"}}'],
        ];
    }
}
