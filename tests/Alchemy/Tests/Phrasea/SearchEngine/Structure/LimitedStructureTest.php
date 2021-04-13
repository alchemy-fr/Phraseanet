<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\LimitedStructure;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

/**
 * @group unit
 * @group structure
 */
class LimitedStructureTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUnrestrictedFields()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING);
        $wrapped = $this->prophesize(Structure::class);
        $wrapped
            ->getUnrestrictedFields()
            ->shouldBeCalled()
            ->willReturn(['foo' => $field]);
        $options = $this->prophesize(SearchEngineOptions::class);
        $structure = new LimitedStructure($wrapped->reveal(), $options->reveal());

        $s = $structure->getUnrestrictedFields();

        $this->assertEquals(['foo' => $field], $structure->getUnrestrictedFields());
    }

    public function testGet()
    {
        $wrapped = $this->prophesize(Structure::class);
        $options = $this->prophesize(SearchEngineOptions::class);
        $options->getBusinessFieldsOn()->willReturn([2]);
        $structure = new LimitedStructure($wrapped->reveal(), $options->reveal());

        $wrapped->get('foo')
            ->shouldBeCalled()
            ->willReturn(
                new Field('foo', FieldMapping::TYPE_STRING, [
                    'used_by_collections' => [1, 2, 3],
                    'used_by_databoxes' => [1]
                ])
            )
        ;
        $this->assertEquals(
            new Field('foo', FieldMapping::TYPE_STRING, [
                'used_by_collections' => [2],
                'used_by_databoxes' => [1]
            ]),
            $structure->get('foo')
        );

        $wrapped->get('bar')->shouldBeCalled();
        $this->assertNull($structure->get('bar'));
    }

    public function testGetAllFields()
    {
        $options = $this->prophesize(SearchEngineOptions::class);
        $options->getBusinessFieldsOn()->willReturn([1, 3]);
        $wrapped = $this->prophesize(Structure::class);
        $structure = new LimitedStructure($wrapped->reveal(), $options->reveal());

        $wrapped->getAllFields()->willReturn([
            'foo' => new Field('foo', FieldMapping::TYPE_STRING, [
                'private' => false,
                'used_by_collections' => [1, 2, 3],
                'used_by_databoxes' => [1]
            ]),
            'bar' => new Field('bar', FieldMapping::TYPE_STRING, [
                'private' => true,
                'used_by_collections' => [1, 2, 3],
                'used_by_databoxes' => [1]
            ])
        ]);
        $this->assertEquals([
            'foo' => new Field('foo', FieldMapping::TYPE_STRING, [
                'private' => false,
                'used_by_collections' => [1, 2, 3],
                'used_by_databoxes' => [1]
            ]),
            'bar' => new Field('bar', FieldMapping::TYPE_STRING, [
                'private' => true,
                'used_by_collections' => [1, 3],
                'used_by_databoxes' => [1]
            ])
        ], $structure->getAllFields());
    }

    private function getCollectionStub($base_id)
    {
        $prophecy = $this->prophesize(\collection::class);
        $prophecy->get_base_id()->willReturn($base_id);
        return $prophecy->reveal();
    }
}
