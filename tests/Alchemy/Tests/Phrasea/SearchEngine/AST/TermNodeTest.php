<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Context;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\TermNode;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class TermNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(TermNode::class, '__toString'), 'Class does not have method __toString');
        $node = new TermNode('foo');
        $this->assertEquals('<term:"foo">', (string) $node);
        $node_with_context = new TermNode('foo', new Context('bar'));
        $this->assertEquals('<term:"foo" context:"bar">', (string) $node_with_context);
    }

    public function testQueryBuild()
    {
        $query_context = $this->prophesize(QueryContext::class);
        $query_context
            ->getLocalizedFields()
            ->willReturn(['foo.fr', 'foo.en']);
        $query_context
            ->getAllowedPrivateFields()
            ->willReturn([]);
        $query_context
            ->getFields()
            ->willReturn(['foo']);

        $node = new TermNode('bar');
        $node->setConcepts([
            new Concept('/baz'),
            new Concept('/qux'),
        ]);
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "bool": {
                "should": [{
                    "multi_match": {
                        "fields": ["concept_path.foo"],
                        "query": "/baz"
                    }
                }, {
                    "multi_match": {
                        "fields": ["concept_path.foo"],
                        "query": "/qux"
                    }
                }]
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
