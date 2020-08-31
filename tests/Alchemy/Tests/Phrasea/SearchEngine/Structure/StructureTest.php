<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure as Structure;

/**
 * @group unit
 * @group structure
 */
class StructureTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptiness()
    {
        $structure = new Structure();
        $this->assertEmpty($structure->getAllFields());
        $this->assertEmpty($structure->getUnrestrictedFields());
        $this->assertEmpty($structure->getPrivateFields());
        $this->assertEmpty($structure->getThesaurusEnabledFields());
        $this->assertEmpty($structure->getDateFields());
    }

    public function testFieldAdd()
    {
        $structure = new Structure();

        $field = $this->prophesize(Field::class);
        $field->getName()->willReturn('foo');
        $field->getType()->willReturn(FieldMapping::TYPE_TEXT);
        $field->isPrivate()->willReturn(false);
        $field->isFacet()->willReturn(false);
        $field->hasConceptInference()->willReturn(false);
        $field->getDependantCollections()->willReturn(['1']);
        $field->get_databox_id()->willReturn('1');

        $structure->add($field->reveal());
        $this->assertCount(1, $structure->getAllFields());

        $conflicting_field = new Field('foo', FieldMapping::TYPE_TEXT, ['2']);

        $merged = new Field('foo', FieldMapping::TYPE_TEXT, ['1', '2']);

        $field->mergeWith($conflicting_field)->willReturn($merged);
        // Should still have only one (both have the same name)
        $structure->add($conflicting_field);
        $this->assertCount(1, $fields = $structure->getAllFields());
        $this->assertInternalType('array', $fields);
        $this->assertSame($merged, reset($fields));
    }

    public function testFieldMerge()
    {
        $field = $this->prophesize(Field::class);
        $field->getName()->willReturn('foo');
        $field->getType()->willReturn(FieldMapping::TYPE_TEXT);
        $field->isPrivate()->willReturn(false);
        $field->isFacet()->willReturn(false);
        $field->hasConceptInference()->willReturn(false);
        $field->get_databox_id()->willReturn('1');

        $other = new Field('foo', FieldMapping::TYPE_TEXT);

        $merged = new Field('foo', FieldMapping::TYPE_TEXT);
        $field->mergeWith($other)->shouldBeCalled()->willReturn($merged);

        $structure = new Structure();
        $structure->add($field->reveal());
        $structure->add($other);
        $this->assertEquals($merged, $structure->get('foo'));
    }

    public function testFieldsRestrictions()
    {
        $structure = new Structure();
        $unrestricted_field = new Field('foo', FieldMapping::TYPE_TEXT, ['private' => false]);
        $structure->add($unrestricted_field);
        $private_field = new Field('bar', FieldMapping::TYPE_TEXT, ['private' => true]);
        $structure->add($private_field);
        // All
        $all_fields = $structure->getAllFields();
        $this->assertContains($unrestricted_field, $all_fields);
        $this->assertContains($private_field, $all_fields);
        // Unrestricted
        $unrestricted_fields = $structure->getUnrestrictedFields();
        $this->assertContains($unrestricted_field, $unrestricted_fields);
        $this->assertNotContains($private_field, $unrestricted_fields);
        // Private
        $private_fields = $structure->getPrivateFields();
        $this->assertContains($private_field, $private_fields);
        $this->assertNotContains($unrestricted_field, $private_fields);
    }

    public function testGetDateFields()
    {
        $string = new Field('foo', FieldMapping::TYPE_TEXT);
        $date = new Field('bar', FieldMapping::TYPE_DATE);
        $structure = new Structure();
        $structure->add($string);
        $this->assertNotContains($string, $structure->getDateFields());
        $structure->add($date);
        $date_fields = $structure->getDateFields();
        $this->assertContains($date, $date_fields);
        $this->assertNotContains($string, $date_fields);
    }

    public function testGetThesaurusEnabledFields()
    {
        $not_enabled = new Field('foo', FieldMapping::TYPE_TEXT, [
            'thesaurus_roots' => null
        ]);
        $enabled = new Field('bar', FieldMapping::TYPE_TEXT, [
            'thesaurus_roots' => [new Concept('/foo')]
        ]);
        $structure = new Structure();
        $structure->add($not_enabled);
        $this->assertNotContains($not_enabled, $structure->getThesaurusEnabledFields());
        $structure->add($enabled);
        $enabled_fields = $structure->getThesaurusEnabledFields();
        $this->assertContains($enabled, $enabled_fields);
        $this->assertNotContains($not_enabled, $enabled_fields);
    }

    public function testGet()
    {
        $structure = new Structure();
        $field = new Field('foo', FieldMapping::TYPE_TEXT);
        $structure->add($field);
        $this->assertEquals($field, $structure->get('foo'));
        $this->assertNull($structure->get('bar'));
    }

    public function testTypeCheck()
    {
        $structure = new Structure();
        $structure->add(new Field('foo', FieldMapping::TYPE_TEXT));
        $structure->add(new Field('bar', FieldMapping::TYPE_DATE));
        $structure->add(new Field('baz', FieldMapping::TYPE_DOUBLE));
        $this->assertEquals(FieldMapping::TYPE_TEXT, $structure->typeOf('foo'));
        $this->assertEquals(FieldMapping::TYPE_DATE, $structure->typeOf('bar'));
        $this->assertEquals(FieldMapping::TYPE_DOUBLE, $structure->typeOf('baz'));
    }

    public function testPrivateCheck()
    {
        $structure = new Structure();
        $structure->add(new Field('foo', FieldMapping::TYPE_TEXT, ['private' => false]));
        $structure->add(new Field('bar', FieldMapping::TYPE_TEXT, ['private' => true]));
        $this->assertFalse($structure->isPrivate('foo'));
        $this->assertTrue($structure->isPrivate('bar'));
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessageRegExp #field#u
     */
    public function testPrivateCheckWithInvalidField()
    {
        $structure = new Structure();
        $structure->isPrivate('foo');
    }

    public function testCollectionsUsedByPrivateFields()
    {
        $structure = new Structure();
        $structure->add($foo = (new Field('foo', FieldMapping::TYPE_TEXT, [
            'private' => true,
            'used_by_collections' => [1, 2]
        ])));
        $structure->add(new Field('foo', FieldMapping::TYPE_TEXT, [
            'private' => true,
            'used_by_collections' => [2, 3]
        ]));
        $structure->add(new Field('bar', FieldMapping::TYPE_TEXT, [
            'private' => true,
            'used_by_collections' => [2, 3]
        ]));
        $structure->add(new Field('baz', FieldMapping::TYPE_TEXT, ['private' => false]));
        $this->assertEquals([1, 2], $foo->getDependantCollections());
        static $expected = [
            'foo' => [1, 2, 3],
            'bar' => [2, 3]
        ];
        $this->assertEquals($expected, $structure->getCollectionsUsedByPrivateFields());
    }
}
