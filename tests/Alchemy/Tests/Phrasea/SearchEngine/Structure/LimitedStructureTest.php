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
        $field = new Field('foo', FieldMapping::TYPE_TEXT);
        $wrapped = $this->prophesize(Structure::class);
        $wrapped
            ->getUnrestrictedFields()
            ->shouldBeCalled()
            ->willReturn(['foo' => $field]);
        $options = $this->prophesize(SearchEngineOptions::class);
        $structure = new LimitedStructure($wrapped->reveal(), $options->reveal());

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
                new Field('foo', FieldMapping::TYPE_TEXT, [
                    'used_by_collections' => [1, 2, 3]
                ])
            )
        ;
        $this->assertEquals(
            new Field('foo', FieldMapping::TYPE_TEXT, [
                'used_by_collections' => [2]
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
            'foo' => new Field('foo', FieldMapping::TYPE_TEXT, [
                'private' => false,
                'used_by_collections' => [1, 2, 3]
            ]),
            'bar' => new Field('bar', FieldMapping::TYPE_TEXT, [
                'private' => true,
                'used_by_collections' => [1, 2, 3]
            ])
        ]);
        $this->assertEquals([
            'foo' => new Field('foo', FieldMapping::TYPE_TEXT, [
                'private' => false,
                'used_by_collections' => [1, 2, 3]
            ]),
            'bar' => new Field('bar', FieldMapping::TYPE_TEXT, [
                'private' => true,
                'used_by_collections' => [1, 3]
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
