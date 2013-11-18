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

        $conf = self::$DI['app']['configuration']->getConfig();
        $conf['registration-fields'] = array('plop');
        self::$DI['app']['configuration'] = $conf;

        $this->assertEquals(array('plop'), self::$DI['app']['registration.fields']);
        $this->assertEquals(array('plop'), self::$DI['app']['registration.fields']);

        $this->assertInternalType('array', self::$DI['app']['registration.optional-fields']);
    }
}
