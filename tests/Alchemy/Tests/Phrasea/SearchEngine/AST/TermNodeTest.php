<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Context;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\TermNode;
use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
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
        $field = new Field('foo', FieldMapping::TYPE_TEXT, ['private' => false]);
        $query_context = $this->prophesize(QueryContext::class);
        $query_context
            ->getUnrestrictedFields()
            ->willReturn([$field]);
        $query_context
            ->getPrivateFields()
            ->willReturn([]);

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

    public function testQueryBuildWithZeroConcept()
    {
        $field = new Field('foo', FieldMapping::TYPE_TEXT, ['private' => false]);
        $query_context = $this->prophesize(QueryContext::class);
        $query_context
            ->getUnrestrictedFields()
            ->willReturn([$field]);
        $query_context
            ->getPrivateFields()
            ->willReturn([]);

        $node = new TermNode('bar');
        $query = $node->buildQuery($query_context->reveal());

        $this->assertNull($query);
    }

    public function testQueryBuildWithPrivateFields()
    {
        $public_field = new Field('foo', FieldMapping::TYPE_TEXT, ['private' => false]);
        $private_field = new Field('bar', FieldMapping::TYPE_TEXT, [
            'private' => true,
            'used_by_collections' => [1, 2, 3]
        ]);

        $query_context = $this->prophesize(QueryContext::class);
        $query_context
            ->getUnrestrictedFields()
            ->willReturn([$public_field]);
        $query_context
            ->getPrivateFields()
            ->willReturn([$private_field]);

        $node = new TermNode('baz');
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
                }, {
                    "filtered": {
                        "filter": {
                            "terms": {
                                "base_id": [1, 2, 3]
                            }
                        },
                        "query": {
                            "bool": {
                                "should": [{
                                    "multi_match": {
                                        "fields": [
                                            "concept_path.bar",
                                            "concept_path.foo"
                                        ],
                                        "query": "/baz"
                                    }
                                }, {
                                    "multi_match": {
                                        "fields": [
                                            "concept_path.bar",
                                            "concept_path.foo"
                                        ],
                                        "query": "/qux"
                                    }
                                }]
                            }
                        }
                    }
                }]
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
