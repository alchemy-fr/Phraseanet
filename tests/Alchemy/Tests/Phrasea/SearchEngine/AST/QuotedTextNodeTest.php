<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\QuotedTextNode;
use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
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
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['private' => false]);
        $query_context = $this->prophesize(QueryContext::class);
        $query_context->getUnrestrictedFields()->willReturn([$field]);
        $query_context->getPrivateFields()->willReturn([]);
        $query_context->localizeField($field)->willReturn(['foo.fr', 'foo.en']);

        $node = new QuotedTextNode('bar');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "multi_match": {
                "type": "phrase",
                "fields": ["foo.fr", "foo.en"],
                "query": "bar",
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
            ->getPrivateFields()
            ->willReturn([$private_field]);
        $query_context
            ->localizeField($public_field)
            ->willReturn(['foo.fr', 'foo.en']);
        $query_context
            ->localizeField($private_field)
            ->willReturn(['private_caption.bar.fr', 'private_caption.bar.en']);

        $node = new QuotedTextNode('baz');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "bool": {
                "should": [{
                    "multi_match": {
                        "type": "phrase",
                        "fields": ["foo.fr", "foo.en"],
                        "query": "baz",
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
                                "type": "phrase",
                                "fields": ["private_caption.bar.fr", "private_caption.bar.en"],
                                "query": "baz",
                                "lenient": true
                            }
                        }
                    }
                }]
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
