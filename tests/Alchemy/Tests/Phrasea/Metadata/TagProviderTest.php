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

    public function testGetAll()
    {
        $this->markTestSkipped('This test lasts more than 30 seconds');
        $all = $this->object->getAll();
        $this->assertArrayHasKey('Phraseanet', $all);
        $this->assertCount(20, $all['Phraseanet']);
    }

    public function testGetLookupTable()
    {
        $lookup = $this->object->getLookupTable();
        $this->assertArrayHasKey('phraseanet', $lookup);
        $this->assertCount(20, $lookup['phraseanet']);
    }
}
