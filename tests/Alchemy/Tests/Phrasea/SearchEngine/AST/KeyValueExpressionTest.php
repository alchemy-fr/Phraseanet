<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Key;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValueExpression;
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
        $node = new KeyValueExpression(Key::database(), 'bar');
        $this->assertEquals('<database:bar>', (string) $node);
    }

    public function testQueryBuild()
    {
        $query_context = $this->prophesize(QueryContext::class);
        $key = $this->prophesize(Key::class);
        $key->getIndexField()->willReturn('foo');

        $node = new KeyValueExpression($key->reveal(), 'bar');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "term": {
                "foo": "bar"
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
