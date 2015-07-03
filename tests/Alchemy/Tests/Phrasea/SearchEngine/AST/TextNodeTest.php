<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Context;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\TextNode;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class QueryContextTest extends \PHPUnit_Framework_TestCase
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
        $query_context = $this->prophesize(QueryContext::class);
        $query_context->getLocalizedFields()->willReturn(['foo.fr', 'foo.en']);
        $query_context->getAllowedPrivateFields()->willReturn([]);

        $node = new TextNode('bar', new Context('baz'));
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "multi_match": {
                "fields": ["foo.fr", "foo.en"],
                "query": "bar",
                "operator": "and"
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }

    public function testQueryBuildWithPrivateFields()
    {
        $public_field = new Field('foo', Mapping::TYPE_STRING, ['private' => false]);
        $private_field = new Field('bar', Mapping::TYPE_STRING, ['private' => true]);

        $query_context = $this->prophesize(QueryContext::class);
        $query_context
            ->getLocalizedFields()
            ->willReturn(['foo.fr', 'foo.en']);
        $query_context
            ->getAllowedPrivateFields()
            ->willReturn([$private_field]);
        $query_context
            ->getAllowedCollectionsOnPrivateField($private_field)
            ->willReturn([1, 2, 3]);
        $query_context
            ->localizeField('private_caption.bar')
            ->willReturn(['private_caption.bar.fr', 'private_caption.bar.en']);

        $node = new TextNode('baz');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "bool": {
                "should": [{
                    "multi_match": {
                        "fields": ["foo.fr", "foo.en"],
                        "query": "baz",
                        "operator": "and"
                    }
                }, {
                    "bool": {
                        "must": [{
                            "terms": {
                                "base_id": [1, 2, 3]
                            }
                        }, {
                            "multi_match": {
                                "fields": ["private_caption.bar.fr", "private_caption.bar.en"],
                                "query": "baz",
                                "operator": "and"
                            }
                        }]
                    }
                }]
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }

    public function testQueryBuildWithConcepts()
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
                        "operator": "and"
                    }
                }, {
                    "multi_match": {
                        "fields": [
                            "concept_path.foo"
                        ],
                        "query": "/qux"
                    }
                }]
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
