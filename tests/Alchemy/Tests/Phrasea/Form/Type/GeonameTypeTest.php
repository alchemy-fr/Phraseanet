<?php

namespace Alchemy\Tests\Phrasea\Form\Type;

use Alchemy\Phrasea\Form\Type\GeonameType;

class GeonameTypeTest extends \PhraseanetTestCase
{
    public function testGetParent()
    {
        $geoname = new GeonameType();
        $this->assertEquals('text', $geoname->getParent());
    }

    public function testGetName()
    {
        $geoname = new GeonameType();
        $this->assertEquals('geoname', $geoname->getName());
    }
}
