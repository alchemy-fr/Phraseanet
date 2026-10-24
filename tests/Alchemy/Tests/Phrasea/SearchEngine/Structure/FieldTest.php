<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;

#[\PHPUnit\Framework\Attributes\Group('unit')]
#[\PHPUnit\Framework\Attributes\Group('structure')]
class FieldTest extends \PHPUnit\Framework\TestCase
{
    public function testBasicMerge()
    {
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['used_by_collections' => ['1', '2'],
                                                              'used_by_databoxes' => [1]]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, ['used_by_collections' => ['3', '4'],
                                                              'used_by_databoxes' => [1]]);
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

    public function testConflictingNameMerge()
    {
        $this->expectException(\Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException::class);
        $this->expectExceptionMessageMatches('#name#u');
        $field = new Field('foo', FieldMapping::TYPE_STRING);
        $other = new Field('bar', FieldMapping::TYPE_STRING);
        $field->mergeWith($other);
    }

    public function testConflictingTypeMerge()
    {
        $this->expectException(\Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException::class);
        $this->expectExceptionMessageMatches('#type#u');
        $field = new Field('foo', FieldMapping::TYPE_STRING);
        $other = new Field('foo', FieldMapping::TYPE_DATE);
        $field->mergeWith($other);
    }

    public function testMixedSearchabilityMerge()
    {
        $this->expectException(\Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException::class);
        $this->expectExceptionMessageMatches('#search#u');
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['searchable' => true]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, ['searchable' => false]);
        $field->mergeWith($other);
    }

    public function testMixedPrivateAndPublicMerge()
    {
        $this->expectException(\Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException::class);
        $this->expectExceptionMessageMatches('#private#u');
        $field = new Field('foo', FieldMapping::TYPE_STRING, ['private' => true]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, ['private' => false]);
        $field->mergeWith($other);
    }

    public function testMixedFacetEligibilityMerge()
    {
        $this->expectException(\Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException::class);
        $this->expectExceptionMessageMatches('#facet#u');
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
            'used_by_collections' => [1, 2],
            'used_by_databoxes' => [1]
        ]);
        $other = new Field('foo', FieldMapping::TYPE_STRING, [
            'used_by_collections' => [2, 3],
            'used_by_databoxes' => [1]
        ]);
        $merged = $field->mergeWith($other);
        $this->assertEquals([1, 2, 3], $merged->getDependantCollections());
    }
}
