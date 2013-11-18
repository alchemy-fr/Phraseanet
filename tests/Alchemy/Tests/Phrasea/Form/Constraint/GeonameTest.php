<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\Geoname;
use Alchemy\Geonames\Geoname as GeonameResult;
use Alchemy\Geonames\Exception\NotFoundException;
use Alchemy\Geonames\Exception\TransportException;

class GeonameTest extends \PhraseanetPHPUnitAbstract
{
    public function testAValidGeonameIsValid()
    {
        $connector = $this->getConnectorMock();
        $connector->expects($this->once())
            ->method('geoname')
            ->with(123456)
            ->will($this->returnValue(new GeonameResult([])));

        $constraint = new Geoname($connector);
        $this->assertTrue($constraint->isValid(123456));
    }

    public function testATransportErrorIsIgnored()
    {
        $connector = $this->getConnectorMock();
        $connector->expects($this->once())
            ->method('geoname')
            ->with(123456)
            ->will($this->throwException(new TransportException()));

        $constraint = new Geoname($connector);
        $this->assertTrue($constraint->isValid(123456));
    }

    public function testAResourceNotFoundReturnFalse()
    {
        $connector = $this->getConnectorMock();
        $connector->expects($this->once())
            ->method('geoname')
            ->with(123456)
            ->will($this->throwException(new NotFoundException()));

        $constraint = new Geoname($connector);
        $this->assertFalse($constraint->isValid(123456));
    }

    private function getConnectorMock()
    {
        return $this->getMockBuilder('Alchemy\Geonames\Connector')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
