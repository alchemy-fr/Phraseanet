<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\EqualExpression;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Key;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field as StructureField;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class EqualExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(EqualExpression::class, '__toString'), 'Class does not have method __toString');
        $key = $this->prophesize(Key::class);
        $key->__toString()->willReturn('foo');
        $node = new EqualExpression($key->reveal(), 'bar');
        $this->assertEquals('(<foo> == <value:"bar">)', (string) $node);
    }

    /**
     * @dataProvider queryProvider
     */
    public function testQueryBuild($index_field, $value, $compatible_value, $private, $expected_json)
    {
        $query_context = $this->prophesize(QueryContext::class)->reveal();

        $key = $this->prophesize(Key::class);
        $key->isValueCompatible($value, $query_context)->willReturn($compatible_value);
        $key->getIndexField($query_context, true)->willReturn($index_field);
        $key->__toString()->willReturn('foo');
        // TODO Test keys implementing QueryPostProcessor

        $node = new EqualExpression($key->reveal(), 'bar');
        $query = $node->buildQuery($query_context);

        $this->assertEquals(json_decode($expected_json, true), $query);
    }

    public function queryProvider()
    {
        return [
            // TODO Put this case in another test case
            // ['foo.raw', 'bar', true, true, '{
            //     "filtered": {
            //         "filter": {
            //             "terms": {
            //                 "base_id": ["baz","qux"] } },
            //         "query": {
            //             "term": {
            //                 "foo.raw": "bar" } } } }'],
            ['foo.raw', 'bar', true, false, '{
                "term": {
                    "foo.raw": "bar" } }'],
        ];
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException
     * @expectedExceptionMessageRegExp #"foo"#u
     */
    public function testQueryBuildWithIncompatibleValue()
    {
        $query_context = $this->prophesize(QueryContext::class)->reveal();
        $key = $this->prophesize(Key::class);
        $key->isValueCompatible('bar', $query_context)->willReturn(false);
        $key->getIndexField($query_context, true)->willReturn('foo.raw');
        $key->__toString()->willReturn('foo');

        $node = new EqualExpression($key->reveal(), 'bar');
        $node->buildQuery($query_context);
    }
}
