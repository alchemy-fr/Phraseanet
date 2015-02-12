<?php

namespace Alchemy\Tests\Phrasea\Metadata;

use Alchemy\Phrasea\Metadata\TagProvider;

class TagProviderTest extends \PhraseanetTestCase
{
    private $object;
    public function setUp()
    {
        parent::setUp();
        $this->object = new TagProvider;
    }

    /**
     * @covers Alchemy\Phrasea\Metadata\TagProvider::getAll
     */
    public function testGetAll()
    {
        $this->markTestSkipped('This test lasts more than 30 seconds');
        $all = $this->object->getAll();
        $this->assertArrayHasKey('Phraseanet', $all);
        $this->assertCount(20, $all['Phraseanet']);
    }

    /**
     * @covers Alchemy\Phrasea\Metadata\TagProvider::getLookupTable
     */
    public function testGetLookupTable()
    {
        $lookup = $this->object->getLookupTable();
        $this->assertArrayHasKey('phraseanet', $lookup);
        $this->assertCount(20, $lookup['phraseanet']);
    }
}
