<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;

/**
 * @group unit
 * @group structure
 */
class FieldTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['used_by_collections' => ['1', '2']]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, ['used_by_collections' => ['3', '4']]);
        $merged = $field->mergeWith($other);
        $this->assertInstanceOf(Field::class, $merged);
        $this->assertNotSame($field, $merged);
        $this->assertNotSame($other, $merged);
        $this->assertEquals('foo', $merged->getName());
        $this->assertEquals(FieldMapping::TYPE_STRING, $merged->getType());
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
        $field = new Field('foo', FieldMapping::TYPE_STRING);
        $other = new Field('bar', FieldMapping::TYPE_STRING);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #type#u
     */
    public function testConflictingTypeMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING);
        $other = new Field('foo', FieldMapping::TYPE_DATE);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #search#u
     */
    public function testMixedSearchabilityMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['searchable' => true]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, ['searchable' => false]);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #private#u
     */
    public function testMixedPrivateAndPublicMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['private' => true]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, ['private' => false]);
        $field->mergeWith($other);
    }

    /**
     * @expectedException Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException
     * @expectedExceptionMessageRegExp #facet#u
     */
    public function testMixedFacetEligibilityMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['facet' => Field::FACET_NO_LIMIT]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, ['facet' => Field::FACET_DISABLED]);
        $field->mergeWith($other);
    }

    public function testMergeWithThesaurusRoots()
    {
        $foo = new Concept(1, '/foo');
        $bar = new Concept(2, '/bar');
        $field = new Field('foo', FieldMapping::TYPE_STRING);
        $other = new Field('foo', FieldMapping::TYPE_STRING, [
            'thesaurus_roots' => [$foo, $bar]
        ]);
        $merged = $field->mergeWith($other);
        $this->assertEquals([$foo, $bar], $merged->getThesaurusRoots());

        $foo = new Concept(1, '/foo');
        $bar = new Concept(2, '/bar');
        $field = new Field('foo', FieldMapping::TYPE_STRING, [
            'thesaurus_roots' => [$foo]
        ]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, [
            'thesaurus_roots' => [$bar]
        ]);
        $merged = $field->mergeWith($other);
        $this->assertEquals([$foo, $bar], $merged->getThesaurusRoots());
    }

    public function testMergeWithDependantCollections()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING, [
            'used_by_collections' => [1, 2]
        ]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, [
            'used_by_collections' => [2, 3]
        ]);
        $merged = $field->mergeWith($other);
        $this->assertEquals([1, 2, 3], $merged->getDependantCollections());
    }
}
