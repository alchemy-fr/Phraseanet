<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;

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
        $this->assertEmpty($structure->getFacetFields());
        $this->assertEmpty($structure->getThesaurusEnabledFields());
        $this->assertEmpty($structure->getDateFields());
    }

    public function testFieldAdd()
    {
        $structure = new Structure();

        $field = $this->prophesize(Field::class);
        $field->getName()->willReturn('foo');
        $field->getType()->willReturn(Mapping::TYPE_STRING);
        $field->isPrivate()->willReturn(false);
        $field->isFacet()->willReturn(false);
        $field->hasConceptInference()->willReturn(false);

        $structure->add($field->reveal());
        $this->assertCount(1, $structure->getAllFields());

        $conflicting_field = $this->prophesize(Field::class);
        $conflicting_field->getName()->willReturn('foo');
        $conflicting_field->getType()->willReturn(Mapping::TYPE_STRING);
        $conflicting_field->isPrivate()->willReturn(false);
        $conflicting_field->isFacet()->willReturn(false);
        $conflicting_field->hasConceptInference()->willReturn(false);
        $dummy = $conflicting_field->reveal();

        $field->mergeWith($dummy)->willReturn($dummy);
        // Should still have only one (both have the same name)
        $structure->add($dummy);
        $this->assertCount(1, $structure->getAllFields());
    }

    public function testFieldMerge()
    {
        $field = $this->prophesize(Field::class);
        $field->getName()->willReturn('foo');
        $field->getType()->willReturn(Mapping::TYPE_STRING);
        $field->isPrivate()->willReturn(false);
        $field->isFacet()->willReturn(false);
        $field->hasConceptInference()->willReturn(false);

        $other = new Field('foo', Mapping::TYPE_STRING);

        $merged = new Field('foo', Mapping::TYPE_STRING);
        $field->mergeWith($other)->shouldBeCalled()->willReturn($merged);

        $structure = new Structure();
        $structure->add($field->reveal());
        $structure->add($other);
        $this->assertEquals($merged, $structure->get('foo'));
    }

    public function testFieldsRestrictions()
    {
        $structure = new Structure();
        $unrestricted_field = new Field('foo', Mapping::TYPE_STRING, ['private' => false]);
        $structure->add($unrestricted_field);
        $private_field = new Field('bar', Mapping::TYPE_STRING, ['private' => true]);
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

    public function testGetFacetFields()
    {
        $facet = new Field('foo', Mapping::TYPE_STRING, ['facet' => true]);
        $not_facet = new Field('bar', Mapping::TYPE_STRING, ['facet' => false]);
        $structure = new Structure();
        $structure->add($facet);
        $this->assertContains($facet, $structure->getFacetFields());
        $structure->add($not_facet);
        $facet_fields = $structure->getFacetFields();
        $this->assertContains($facet, $facet_fields);
        $this->assertNotContains($not_facet, $facet_fields);
    }

    public function testGetDateFields()
    {
        $string = new Field('foo', Mapping::TYPE_STRING);
        $date = new Field('bar', Mapping::TYPE_DATE);
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
        $not_enabled = new Field('foo', Mapping::TYPE_STRING, [
            'thesaurus_roots' => null
        ]);
        $enabled = new Field('bar', Mapping::TYPE_STRING, [
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
        $field = new Field('foo', Mapping::TYPE_STRING);
        $structure->add($field);
        $this->assertEquals($field, $structure->get('foo'));
        $this->assertNull($structure->get('bar'));
    }

    public function testTypeCheck()
    {
        $structure = new Structure();
        $structure->add(new Field('foo', Mapping::TYPE_STRING));
        $structure->add(new Field('bar', Mapping::TYPE_DATE));
        $structure->add(new Field('baz', Mapping::TYPE_DOUBLE));
        $this->assertEquals(Mapping::TYPE_STRING, $structure->typeOf('foo'));
        $this->assertEquals(Mapping::TYPE_DATE, $structure->typeOf('bar'));
        $this->assertEquals(Mapping::TYPE_DOUBLE, $structure->typeOf('baz'));
    }

    public function testPrivateCheck()
    {
        $structure = new Structure();
        $structure->add(new Field('foo', Mapping::TYPE_STRING, ['private' => false]));
        $structure->add(new Field('bar', Mapping::TYPE_STRING, ['private' => true]));
        $this->assertFalse($structure->isPrivate('foo'));
        $this->assertTrue($structure->isPrivate('bar'));
    }

    /**
     * @expectedException DomainException
     * @expectedExceptionMessageRegExp #field#u
     */
    public function testPrivateCheckWithInvalidField()
    {
        $structure = new Structure();
        $structure->isPrivate('foo');
    }
}
