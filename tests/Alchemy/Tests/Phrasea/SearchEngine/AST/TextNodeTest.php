<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Context;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\TextNode;
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
class TextNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(TextNode::class, '__toString'), 'Class does not have method __toString');
        $node = new TextNode('foo');
        $this->assertEquals('<text:"foo">', (string) $node);
        $node_with_context = new TextNode('foo', new Context('bar'));
        $this->assertEquals('<text:"foo" context:"bar">', (string) $node_with_context);
    }

    public function testMerge()
    {
        $left = new TextNode('foo', new Context('bar'));
        $right = new TextNode('baz', new Context('qux'));
        $merged = TextNode::merge($left, $right);
        $this->assertEquals(new TextNode('foobaz'), $merged);
    }

    public function testContextAdd()
    {
        $node = new TextNode('foo');
        $node_with_context = $node->withContext(new Context('bar'));
        $this->assertEquals(new TextNode('foo', new Context('bar')), $node_with_context);
    }

    public function testQueryBuild()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['private' => false]);
        $query_context = $this->prophesize(QueryContext::class);
        $query_context->getUnrestrictedFields()->willReturn([$field]);
        $query_context->getPrivateFields()->willReturn([]);
        $query_context->localizeField($field)->willReturn(['foo.fr', 'foo.en']);
        $query_context->truncationField($field)->willReturn([]);

        $node = new TextNode('bar', new Context('baz'));
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "multi_match": {
                "fields": ["foo.fr", "foo.en"],
                "query": "bar",
                "type": "cross_fields",
                "operator": "and",
                "lenient": true
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);

        $query_context->truncationField($field)->willReturn(['foo.truncation', 'foo.truncation']);
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "multi_match": {
                "fields": ["foo.fr", "foo.en", "foo.truncation", "foo.truncation"],
                "query": "bar",
                "type": "cross_fields",
                "operator": "and",
                "lenient": true
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }

    public function testQueryBuildWithPrivateFields()
    {
        $public_field = new Field('foo', FieldMapping::TYPE_STRING, ['private' => false]);
        $private_field = new Field('bar', FieldMapping::TYPE_STRING, [
            'private' => true,
            'used_by_collections' => [1, 2, 3]
        ]);

        $query_context = $this->prophesize(QueryContext::class);
        $query_context
            ->getUnrestrictedFields()
            ->willReturn([$public_field]);
        $query_context
            ->localizeField($public_field)
            ->willReturn(['foo.fr', 'foo.en']);
        $query_context
            ->truncationField($public_field)
            ->willReturn([]);
        $query_context
            ->getPrivateFields()
            ->willReturn([$private_field]);
        $query_context
            ->localizeField($private_field)
            ->willReturn(['private_caption.bar.fr', 'private_caption.bar.en']);
        $query_context
            ->truncationField($private_field)
            ->willReturn([]);

        $node = new TextNode('baz');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "bool": {
                "should": [{
                    "multi_match": {
                        "fields": ["foo.fr", "foo.en"],
                        "query": "baz",
                        "type": "cross_fields",
                        "operator": "and",
                        "lenient": true
                    }
                }, {
                    "filtered": {
                        "filter": {
                            "terms": {
                                "base_id": [1, 2, 3]
                            }
                        },
                        "query": {
                            "multi_match": {
                                "fields": ["private_caption.bar.fr", "private_caption.bar.en"],
                                "query": "baz",
                                "type": "cross_fields",
                                "operator": "and",
                                "lenient": true
                            }
                        }
                    }
                }]
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }

    public function testQueryBuildWithConcepts()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['private' => false]);
        $query_context = $this->prophesize(QueryContext::class);
        $query_context->getUnrestrictedFields()->willReturn([$field]);
        $query_context->getPrivateFields()->willReturn([]);
        $query_context->localizeField($field)->willReturn(['foo.fr', 'foo.en']);
        $query_context->truncationField($field)->willReturn([]);

        $node = new TextNode('bar');
        $node->setConcepts([
            new Concept('/qux'),
        ]);
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "bool": {
                "should": [{
                    "multi_match": {
                        "fields": ["foo.fr", "foo.en"],
                        "query": "bar",
                        "type": "cross_fields",
                        "operator": "and",
                        "lenient": true
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

    public function testQueryBuildWithPrivateFieldAndConcept()
    {
        $public_field = new Field('foo', FieldMapping::TYPE_STRING, ['private' => false]);
        $private_field = new Field('bar', FieldMapping::TYPE_STRING, [
            'private' => true,
            'used_by_collections' => [1, 2, 3]
        ]);

        $query_context = $this->prophesize(QueryContext::class);
        $query_context
            ->getUnrestrictedFields()
            ->willReturn([$public_field]);
        $query_context
            ->localizeField($public_field)
            ->willReturn(['foo.fr', 'foo.en']);
        $query_context
            ->truncationField($public_field)
            ->willReturn([]);
        $query_context
            ->getPrivateFields()
            ->willReturn([$private_field]);
        $query_context
            ->localizeField($private_field)
            ->willReturn(['private_caption.bar.fr', 'private_caption.bar.en']);
        $query_context
            ->truncationField($private_field)
            ->willReturn([]);

        $node = new TextNode('baz');
        $node->setConcepts([
            new Concept('/qux'),
        ]);
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "bool": {
                "should": [{
                    "multi_match": {
                        "fields": ["foo.fr", "foo.en"],
                        "query": "baz",
                        "type": "cross_fields",
                        "operator": "and",
                        "lenient": true
                    }
                }, {
                    "multi_match": {
                        "fields": [
                            "concept_path.foo"
                        ],
                        "query": "\/qux"
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
                                        "fields": ["private_caption.bar.fr", "private_caption.bar.en"],
                                        "query": "baz",
                                        "type": "cross_fields",
                                        "operator": "and",
                                        "lenient": true
                                    }
                                }, {
                                    "multi_match": {
                                        "fields": ["concept_path.bar"],
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
