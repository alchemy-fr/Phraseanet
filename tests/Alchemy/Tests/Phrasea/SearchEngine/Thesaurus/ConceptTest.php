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
        $concept = new Concept('/foo/bar');
        $this->assertEquals('/foo/bar', $concept->getPath());
    }

    public function testNarrowCheck()
    {
        $parent = new Concept('/foo');
        $child = new Concept('/foo/bar');
        $this->assertFalse($parent->isNarrowerThan($child));
        $this->assertTrue($child->isNarrowerThan($parent));
        $other = new Concept('/other/bar');
        $this->assertFalse($other->isNarrowerThan($child));
    }

    public function testNarrowConceptPruning()
    {
        $concepts = [
            new Concept('/foo'),
            new Concept('/fooo'),
            new Concept('/foo/baz'),
            new Concept('/bar/baz'),
            new Concept('/bar'),
        ];
        $pruned = Concept::pruneNarrowConcepts($concepts);
        $expected = [
            new Concept('/bar'),
            new Concept('/foo'),
            new Concept('/fooo'),
        ];
        $this->assertEquals($expected, $pruned);
    }
}
