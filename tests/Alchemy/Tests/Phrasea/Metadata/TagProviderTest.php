<?php

namespace Alchemy\Tests\Phrasea\Metadata;

use Alchemy\Phrasea\Metadata\TagProvider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class TagProviderTest extends \PhraseanetTestCase
{
    private $object;
    public function setUp(): void
    {
        parent::setUp();
        $this->object = new TagProvider;
    }

    public function testGetLookupTable()
    {
        $lookup = $this->object->getLookupTable();
        $this->assertArrayHasKey('phraseanet', $lookup);
        $this->assertCount(20, $lookup['phraseanet']);
        $this->assertEquals([
            'tagname'   => 'tf-filename',
            'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfFilename',
            'namespace' => 'Phraseanet',
        ], $lookup['phraseanet']['tf-filename']);
        $this->assertArrayHasKey('iptc', $lookup);
        $this->assertEquals('Keywords', $lookup['iptc']['keywords']['tagname']);
        $this->assertEquals('IPTC', $lookup['iptc']['keywords']['namespace']);
    }
}
