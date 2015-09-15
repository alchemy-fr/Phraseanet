<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\FlagStatement;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class FlagStatementTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(FlagStatement::class, '__toString'), 'Class does not have method __toString');
        $node = new FlagStatement('foo', true);
        $this->assertEquals('<flag:foo set>', (string) $node);
        $node = new FlagStatement('foo', false);
        $this->assertEquals('<flag:foo cleared>', (string) $node);
    }

    public function testQueryBuild()
    {
        $query_context = $this->prophesize(QueryContext::class);

        $node = new FlagStatement('foo', true);
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "term": {
                "flags.foo": true
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
