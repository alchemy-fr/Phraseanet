<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
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
        $field = new Field('foo', FieldMapping::TYPE_TEXT, ['used_by_collections' => ['1', '2']]);
        $other = new Field('foo', FieldMapping::TYPE_TEXT, ['used_by_collections' => ['3', '4']]);
        $merged = $field->mergeWith($other);
        $this->assertInstanceOf(Field::class, $merged);
        $this->assertNotSame($field, $merged);
        $this->assertNotSame($other, $merged);
        $this->assertEquals('foo', $merged->getName());
        $this->assertEquals(FieldMapping::TYPE_TEXT, $merged->getType());
        $this->assertTrue($merged->isSearchable());
        $this->assertFalse($merged->isPrivate());
        $this->assertFalse($merged->isFacet());
        $this->assertNull($merged->getThesaurusRoots());
        $this->assertEquals(['1', '2', '3', '4'], $merged->getDependantCollections());
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #name#u
     */
    public function testConflictingNameMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_TEXT);
        $other = new Field('bar', FieldMapping::TYPE_TEXT);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #type#u
     */
    public function testConflictingTypeMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_TEXT);
        $other = new Field('foo', FieldMapping::TYPE_DATE);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #search#u
     */
    public function testMixedSearchabilityMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_TEXT, ['searchable' => true]);
        $other = new Field('foo', FieldMapping::TYPE_TEXT, ['searchable' => false]);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #private#u
     */
    public function testMixedPrivateAndPublicMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_TEXT, ['private' => true]);
        $other = new Field('foo', FieldMapping::TYPE_TEXT, ['private' => false]);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #facet#u
     */
    public function testMixedFacetEligibilityMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_TEXT, ['facet' => Field::FACET_NO_LIMIT]);
        $other = new Field('foo', FieldMapping::TYPE_TEXT, ['facet' => Field::FACET_DISABLED]);
        $field->mergeWith($other);
    }

    public function testMergeWithThesaurusRoots()
    {
        $foo = new Concept('/foo');
        $bar = new Concept('/bar');
        $field = new Field('foo', FieldMapping::TYPE_TEXT);
        $other = new Field('foo', FieldMapping::TYPE_TEXT, [
            'thesaurus_roots' => [$foo, $bar]
        ]);
        $merged = $field->mergeWith($other);
        $this->assertEquals([$foo, $bar], $merged->getThesaurusRoots());

        $foo = new Concept('/foo');
        $bar = new Concept('/bar');
        $field = new Field('foo', FieldMapping::TYPE_TEXT, [
            'thesaurus_roots' => [$foo]
        ]);
        $other = new Field('foo', FieldMapping::TYPE_TEXT, [
            'thesaurus_roots' => [$bar]
        ]);
        $merged = $field->mergeWith($other);
        $this->assertEquals([$foo, $bar], $merged->getThesaurusRoots());
    }

    public function testMergeWithDependantCollections()
    {
        $field = new Field('foo', FieldMapping::TYPE_TEXT, [
            'used_by_collections' => [1, 2]
        ]);
        $other = new Field('foo', FieldMapping::TYPE_TEXT, [
            'used_by_collections' => [2, 3]
        ]);
        $merged = $field->mergeWith($other);
        $this->assertEquals([1, 2, 3], $merged->getDependantCollections());
    }
}
