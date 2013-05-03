<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider
 */
class RegistrationServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testSameInstanceShouldBereturnedEveryTime()
    {
        self::$DI['app']->register(new RegistrationServiceProvider());

        self::$DI['app']['phraseanet.configuration'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['phraseanet.configuration']->expects($this->once())
            ->method('has')
            ->with('registration-fields')
            ->will($this->returnValue(true));
        self::$DI['app']['phraseanet.configuration']->expects($this->once())
            ->method('get')
            ->with('registration-fields')
            ->will($this->returnValue(array('plop')));

        $this->assertEquals(array('plop'), self::$DI['app']['registration.fields']);
        $this->assertEquals(array('plop'), self::$DI['app']['registration.fields']);

        $this->assertInternalType('array', self::$DI['app']['registration.optional-fields']);
    }
}
