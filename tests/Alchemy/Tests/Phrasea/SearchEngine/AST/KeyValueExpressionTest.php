<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Key;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Expression as KeyValueExpression;
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
        $this->assertEquals('<foo:bar>', (string) $node);
    }

    public function testQueryBuild()
    {
        $query_context = $this->prophesize(QueryContext::class);
        $key = $this->prophesize(Key::class);
        $key->buildQueryForValue('bar', $query_context->reveal())->willReturn('baz');

        $node = new KeyValueExpression($key->reveal(), 'bar');
        $query = $node->buildQuery($query_context->reveal());
        $this->assertEquals('baz', $query);
    }
}
