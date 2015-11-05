<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Key;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Expression as KeyValueExpression;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\MetadataKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\NativeKey;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class KeyValueExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(KeyValueExpression::class, '__toString'), 'Class does not have method __toString');
        $key = $this->prophesize(Key::class);
        $key->__toString()->willReturn('foo');
        $node = new KeyValueExpression($key->reveal(), 'bar');
        $this->assertEquals('<foo:"bar">', (string) $node);
    }

    /**
     * @dataProvider keyProvider
     */
    public function testQueryBuild($key, $value, $result)
    {
        $query_context = $this->prophesize(QueryContext::class);
        $node = new KeyValueExpression($key, $value);
        $query = $node->buildQuery($query_context->reveal());
        $this->assertEquals(json_decode($result, true), $query);
    }

    public function keyProvider()
    {
        return [
            [NativeKey::database(),         'foo', '{"term":{"databox_name": "foo"}}'],
            [NativeKey::collection(),       'bar', '{"term":{"collection_name": "bar"}}'],
            [NativeKey::mediaType(),        'baz', '{"term":{"type": "baz"}}'],
            [NativeKey::recordIdentifier(), 'qux', '{"term":{"record_id": "qux"}}'],
            [new MetadataKey('foo'),        'bar', '{"term":{"exif.foo": "bar"}}'],
        ];
    }
}
