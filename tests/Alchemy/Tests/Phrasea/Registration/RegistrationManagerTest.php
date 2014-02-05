<?php

namespace Alchemy\Tests\Phrasea\Registration;

use Alchemy\Phrasea\Registration\RegistrationManager;

class RegistrationManagerTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider getRegistrationProvider
     */
    public function testRegistrationIsEnabled($enabledOnColl, $expected)
    {
        $mockColl = $this->getMockBuilder('\collection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockColl->expects($this->once())->method('isRegistrationEnabled')->will($this->returnValue($enabledOnColl));

        $mockDatabox = $this->getMockBuilder('\databox')
            ->disableOriginalConstructor()
            ->getMock();

        $mockAppbox = $this->getMockBuilder('\appbox')
            ->disableOriginalConstructor()
            ->getMock();

        $mockColl->expects($this->once())->method('isRegistrationEnabled')->will($this->returnValue(false));

        $mockDatabox->expects($this->once())->method('get_collections')->will($this->returnValue([$mockColl]));
        $mockAppbox->expects($this->once())->method('get_databoxes')->will($this->returnValue([$mockDatabox]));

        $service = new RegistrationManager($mockAppbox);
        $this->assertEquals($expected, $service->isRegistrationEnabled());
    }

    public function getRegistrationProvider()
    {
        return [
            [false, false],
            [true, true],
        ];
    }
}