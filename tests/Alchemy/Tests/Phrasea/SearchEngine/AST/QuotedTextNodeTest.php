<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\QuotedTextNode;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class QuotedTextNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(QuotedTextNode::class, '__toString'), 'Class does not have method __toString');
        $node = new QuotedTextNode('foo');
        $this->assertEquals('<exact_text:"foo">', (string) $node);
    }

    public function testQueryBuild()
    {
        $query_context = $this->prophesize(QueryContext::class);
        $query_context->getLocalizedFields()->willReturn(['foo.fr', 'foo.en']);
        $query_context->getAllowedPrivateFields()->willReturn([]);

        $node = new QuotedTextNode('bar');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "multi_match": {
                "type": "phrase",
                "fields": ["foo.fr", "foo.en"],
                "query": "bar"
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

        $node = new QuotedTextNode('baz');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "bool": {
                "should": [{
                    "multi_match": {
                        "type": "phrase",
                        "fields": ["foo.fr", "foo.en"],
                        "query": "baz"
                    }
                }, {
                    "bool": {
                        "must": [{
                            "terms": {
                                "base_id": [1, 2, 3]
                            }
                        }, {
                            "multi_match": {
                                "type": "phrase",
                                "fields": ["private_caption.bar.fr", "private_caption.bar.en"],
                                "query": "baz"
                            }
                        }]
                    }
                }]
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
