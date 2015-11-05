<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\MetadataKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\MetadataMatchStatement;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class MetdataMatchStatementTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(MetadataMatchStatement::class, '__toString'), 'Class does not have method __toString');
        $key = new MetadataKey('foo');
        $node = new MetadataMatchStatement($key, 'bar');
        $this->assertEquals('<metadata:foo value:"bar">', (string) $node);
    }

    public function testQueryBuild()
    {
        $query_context = $this->prophesize(QueryContext::class);

        $key = new MetadataKey('foo');
        $node = new MetadataMatchStatement($key, 'bar');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "term": {
                "exif.foo": "bar"
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
