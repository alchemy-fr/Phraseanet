<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\RawNode;
use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class RawNodeTest extends \PHPUnit\Framework\TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(RawNode::class, '__toString'), 'Class does not have method __toString');
        $node = new RawNode('foo');
        $this->assertEquals('<raw:"foo">', (string) $node);
    }

    public function testQueryBuildOnSingleField()
    {
        $field = $this->prophesize(Field::class);
        $field->getType()->willReturn(FieldMapping::TYPE_STRING);
        $field->getIndexField(true)->willReturn('foo.raw');

        $query_context = $this->prophesize(QueryContext::class);
        $query_context->getUnrestrictedFields()->willReturn([$field->reveal()]);
        $query_context->getPrivateFields()->willReturn([]);

        $node = new RawNode('bar');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "term": {
                "foo.raw": "bar"
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }

    public function testQueryBuildOnMultipleFields()
    {
        $field_a = $this->prophesize(Field::class);
        $field_a->getType()->willReturn(FieldMapping::TYPE_STRING);
        $field_a->getIndexField(true)->willReturn('foo.raw');
        $field_b = $this->prophesize(Field::class);
        $field_b->getType()->willReturn(FieldMapping::TYPE_STRING);
        $field_b->getIndexField(true)->willReturn('bar.raw');

        $query_context = $this->prophesize(QueryContext::class);
        $query_context->getUnrestrictedFields()->willReturn([
            $field_a->reveal(),
            $field_b->reveal()
        ]);
        $query_context->getPrivateFields()->willReturn([]);

        $node = new RawNode('baz');
        $query = $node->buildQuery($query_context->reveal());

        $expected = '{
            "multi_match": {
                "query": "baz",
                "fields": ["foo.raw", "bar.raw"],
                "analyzer": "keyword"
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
