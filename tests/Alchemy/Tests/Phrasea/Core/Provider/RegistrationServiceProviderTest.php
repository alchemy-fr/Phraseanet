<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class RegistrationServiceProviderTest extends ServiceProviderTestCase
{
    private $fields;

    public function setUp(): void
    {
        parent::setUp();
        $this->fields = self::$DI['app']['conf']->get('registration-fields', []);
    }

    public function tearDown(): void
    {
        self::$DI['app']['conf']->set('registration-fields', $this->fields);
        parent::tearDown();
    }

    public function testSameInstanceShouldBereturnedEveryTime()
    {
        self::$DI['app']->register(new RegistrationServiceProvider());
        self::$DI['app']['conf']->set('registration-fields', ['plop']);

        $this->assertEquals(['plop'], self::$DI['app']['registration.fields']);
        $this->assertEquals(['plop'], self::$DI['app']['registration.fields']);

        $this->assertIsArray(self::$DI['app']['registration.optional-fields']);
    }

    public static function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider', 'registration.manager', 'Alchemy\Phrasea\Core\Configuration\RegistrationManager'],
        ];
    }
}
