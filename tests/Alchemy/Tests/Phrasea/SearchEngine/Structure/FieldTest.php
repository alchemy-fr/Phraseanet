<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

/**
 * @group unit
 * @group structure
 */
class FieldTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicMerge()
    {
        $field = new Field('foo', Mapping::TYPE_STRING);
        $other = new Field('foo', Mapping::TYPE_STRING);
        $merged = $field->mergeWith($other);
        $this->assertInstanceOf(Field::class, $merged);
        $this->assertNotSame($field, $merged);
        $this->assertNotSame($other, $merged);
        $this->assertEquals('foo', $merged->getName());
        $this->assertEquals(Mapping::TYPE_STRING, $merged->getType());
        $this->assertTrue($merged->isSearchable());
        $this->assertFalse($merged->isPrivate());
        $this->assertFalse($merged->isFacet());
        $this->assertNull($merged->getThesaurusRoots());
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #name#u
     */
    public function testConflictingNameMerge()
    {
        $field = new Field('foo', Mapping::TYPE_STRING);
        $other = new Field('bar', Mapping::TYPE_STRING);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #type#u
     */
    public function testConflictingTypeMerge()
    {
        $field = new Field('foo', Mapping::TYPE_STRING);
        $other = new Field('foo', Mapping::TYPE_DATE);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #search#u
     */
    public function testMixedSearchabilityMerge()
    {
        $field = new Field('foo', Mapping::TYPE_STRING, ['searchable' => true]);
        $other = new Field('foo', Mapping::TYPE_STRING, ['searchable' => false]);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #private#u
     */
    public function testMixedPrivateAndPublicMerge()
    {
        $field = new Field('foo', Mapping::TYPE_STRING, ['private' => true]);
        $other = new Field('foo', Mapping::TYPE_STRING, ['private' => false]);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #facet#u
     */
    public function testMixedFacetEligibilityMerge()
    {
        $field = new Field('foo', Mapping::TYPE_STRING, ['facet' => true]);
        $other = new Field('foo', Mapping::TYPE_STRING, ['facet' => false]);
        $field->mergeWith($other);
    }

    public function testMergeWithThesaurusRoots()
    {
        $foo = new Concept('/foo');
        $bar = new Concept('/bar');
        $field = new Field('foo', Mapping::TYPE_STRING);
        $other = new Field('foo', Mapping::TYPE_STRING, [
            'thesaurus_roots' => [$foo, $bar]
        ]);
        $merged = $field->mergeWith($other);
        $this->assertEquals([$foo, $bar], $merged->getThesaurusRoots());

        $foo = new Concept('/foo');
        $bar = new Concept('/bar');
        $field = new Field('foo', Mapping::TYPE_STRING, [
            'thesaurus_roots' => [$foo]
        ]);
        $other = new Field('foo', Mapping::TYPE_STRING, [
            'thesaurus_roots' => [$bar]
        ]);
        $merged = $field->mergeWith($other);
        $this->assertEquals([$foo, $bar], $merged->getThesaurusRoots());
    }
}
