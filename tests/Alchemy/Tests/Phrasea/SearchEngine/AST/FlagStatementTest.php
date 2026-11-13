<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\FlagStatement;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Flag;

#[\PHPUnit\Framework\Attributes\Group('unit')]
#[\PHPUnit\Framework\Attributes\Group('searchengine')]
#[\PHPUnit\Framework\Attributes\Group('ast')]
class FlagStatementTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
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
        $query_context->getFlag('foo')->willReturn(new Flag('bar'));

        $node = new FlagStatement('foo', true);
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "term": {
                "flags.bar": true
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
