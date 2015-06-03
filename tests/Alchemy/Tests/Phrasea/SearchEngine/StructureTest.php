<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;

class StructureTest extends \PHPUnit_Framework_TestCase
{
    public function testFieldMerge()
    {
        $field = new Field('foo', Mapping::TYPE_STRING);
        $other = new Field('foo', Mapping::TYPE_STRING);
        $field->mergeWith($other);
        $this->assertEquals('foo', $field->getName());
        $this->assertEquals(Mapping::TYPE_STRING, $field->getType());
        $this->assertTrue($field->isSearchable());
        $this->assertFalse($field->isPrivate());
        $this->assertFalse($field->isFacet());
    }

    public function testFieldAdd()
    {
        $structure = new Structure();
        $this->assertEmpty($structure->getAllFields());

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

    public function testFieldsRestrictions()
    {
        $structure = new Structure();
        $unrestricted_field = new Field('foo', Mapping::TYPE_STRING, true, false);
        $structure->add($unrestricted_field);
        $private_field = new Field('bar', Mapping::TYPE_STRING, true, true);
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

    public function testPrivateFieldCheck()
    {
        $structure = new Structure();
        $structure->add(new Field('foo', Mapping::TYPE_STRING, true, false)); // Unrestricted field
        $structure->add(new Field('bar', Mapping::TYPE_STRING, true, true)); // Private field
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
