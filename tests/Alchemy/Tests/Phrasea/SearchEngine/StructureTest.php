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
        $structure->add(new Field('foo', Mapping::TYPE_STRING));
        $this->assertCount(1, $structure->getAllFields());
        // Should still have only one (both have the same name)
        $structure->add(new Field('foo', Mapping::TYPE_STRING));
        $this->assertCount(1, $structure->getAllFields());
    }
}
