<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Thesaurus;

use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;

/**
 * @group unit
 * @group thesaurus
 */
class ConceptTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPath()
    {
        $concept = new Concept(1, '/foo/bar');
        $this->assertEquals('/foo/bar', $concept->getPath());
    }

    public function testNarrowCheck()
    {
        $parent = new Concept(1, '/foo');
        $child = new Concept(1, '/foo/bar');
        $this->assertFalse($parent->isNarrowerThan($child));
        $this->assertTrue($child->isNarrowerThan($parent));
        $other = new Concept(1, '/other/bar');
        $this->assertFalse($other->isNarrowerThan($child));
    }

    public function testNarrowConceptPruning()
    {
        $concepts = [
            new Concept(1, '/foo'),
            new Concept(1, '/fooo'),
            new Concept(1, '/foo/baz'),
            new Concept(1, '/bar/baz'),
            new Concept(1, '/bar'),
        ];
        $pruned = Concept::pruneNarrowConcepts($concepts);
        $expected = [
            new Concept(1, '/bar'),
            new Concept(1, '/foo'),
            new Concept(1, '/fooo'),
        ];
        $this->assertEquals($expected, $pruned);
    }
}
