<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Field as ASTField;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\FieldEqualsExpression;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field as StructureField;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class FieldEqualsExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(FieldEqualsExpression::class, '__toString'), 'Class does not have method __toString');
        $field = $this->prophesize(ASTField::class);
        $field->__toString()->willReturn('foo');
        $node = new FieldEqualsExpression($field->reveal(), 'bar');
        $this->assertEquals('(foo == <value:"bar">)', (string) $node);
    }

    /**
     * @dataProvider queryProvider
     */
    public function testQueryBuild($index_field, $value, $compatible_value, $private, $expected_json)
    {
        $structure_field = $this->prophesize(StructureField::class);
        $structure_field->isValueCompatible($value)->willReturn($compatible_value);
        $structure_field->getIndexField(true)->willReturn($index_field);
        $structure_field->isPrivate()->willReturn($private);
        if ($private) {
            $structure_field->getDependantCollections()->willReturn(['baz', 'qux']);
        }

        $ast_field = $this->prophesize(ASTField::class);
        $query_context = $this->prophesize(QueryContext::class);
        $query_context->get($ast_field->reveal())->willReturn($structure_field);

        $node = new FieldEqualsExpression($ast_field->reveal(), 'bar');
        $query = $node->buildQuery($query_context->reveal());

        $this->assertEquals(json_decode($expected_json, true), $query);
    }

    public function queryProvider()
    {
        return [
            ['foo.raw', 'bar', true, true, '{
                "filtered": {
                    "filter": {
                        "terms": {
                            "base_id": ["baz","qux"] } },
                    "query": {
                        "term": {
                            "foo.raw": "bar" } } } }'],
            ['foo.raw', 'bar', true, false, '{
                "term": {
                    "foo.raw": "bar" } }'],
            ['foo.raw', 'bar', false, true, 'null'],
            ['foo.raw', 'bar', false, false, 'null'],
        ];
    }
}
